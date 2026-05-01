import axios from 'axios';
import MockAdapter from 'axios-mock-adapter';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import {
  api,
  performTokenRefresh,
  setAuthFailureCallback,
} from './client';

const BASE_URL = 'http://localhost:8080/api';

const mockApi = new MockAdapter(api);

// Separate adapter for the bare axios instance used inside performTokenRefresh
const mockAxios = new MockAdapter(axios);

beforeEach(() => {
  localStorage.clear();
  setAuthFailureCallback(null);
  mockApi.reset();
  mockAxios.reset();
});

afterEach(() => {
  vi.restoreAllMocks();
});

// ---------------------------------------------------------------------------
// Request interceptor
// ---------------------------------------------------------------------------

describe('request interceptor', () => {
  it('attaches Authorization header when token is in localStorage', async () => {
    localStorage.setItem('token', 'my-token');
    mockApi.onGet('/health').reply(200, {});

    const response = await api.get('/health');

    expect(response.config.headers['Authorization']).toBe('Bearer my-token');
  });

  it('omits Authorization header when no token is stored', async () => {
    mockApi.onGet('/health').reply(200, {});

    const response = await api.get('/health');

    expect(response.config.headers['Authorization']).toBeUndefined();
  });
});

// ---------------------------------------------------------------------------
// performTokenRefresh
// ---------------------------------------------------------------------------

const refreshPayload = {
  token: 'new-token',
  refresh_token: 'new-refresh',
  user: { id: '1', username: 'alice', createdAt: '', updatedAt: '' },
};

describe('performTokenRefresh', () => {
  it('POSTs to /token/refresh and stores new credentials in localStorage', async () => {
    localStorage.setItem('refresh_token', 'old-refresh');
    mockAxios
      .onPost(`${BASE_URL}/token/refresh`)
      .reply(200, refreshPayload);

    const result = await performTokenRefresh();

    expect(result).toEqual(refreshPayload);
    expect(localStorage.getItem('token')).toBe('new-token');
    expect(localStorage.getItem('refresh_token')).toBe('new-refresh');
    expect(JSON.parse(localStorage.getItem('user')!)).toEqual(refreshPayload.user);
  });

  it('clears localStorage and rejects when no refresh token is present', async () => {
    localStorage.setItem('token', 'old-token');

    await expect(performTokenRefresh()).rejects.toThrow('No refresh token available');

    expect(localStorage.getItem('token')).toBeNull();
  });

  it('calls the auth failure callback when no refresh token is present', async () => {
    const cb = vi.fn();
    setAuthFailureCallback(cb);

    await expect(performTokenRefresh()).rejects.toThrow();

    expect(cb).toHaveBeenCalledOnce();
  });

  it('clears localStorage and rejects when the refresh request fails', async () => {
    localStorage.setItem('refresh_token', 'bad-refresh');
    localStorage.setItem('token', 'old-token');
    mockAxios
      .onPost(`${BASE_URL}/token/refresh`)
      .reply(401, { message: 'Invalid refresh token' });

    await expect(performTokenRefresh()).rejects.toBeDefined();

    expect(localStorage.getItem('token')).toBeNull();
    expect(localStorage.getItem('refresh_token')).toBeNull();
  });

  it('calls the auth failure callback when the refresh request fails', async () => {
    const cb = vi.fn();
    setAuthFailureCallback(cb);
    localStorage.setItem('refresh_token', 'bad-refresh');
    mockAxios
      .onPost(`${BASE_URL}/token/refresh`)
      .reply(401, {});

    await expect(performTokenRefresh()).rejects.toBeDefined();

    expect(cb).toHaveBeenCalledOnce();
  });

  it('deduplicates concurrent calls — only one HTTP request is made', async () => {
    localStorage.setItem('refresh_token', 'valid-refresh');

    let resolveRequest!: (v: unknown) => void;
    const requestPromise = new Promise((res) => { resolveRequest = res; });

    mockAxios
      .onPost(`${BASE_URL}/token/refresh`)
      .reply(async () => {
        await requestPromise;
        return [200, refreshPayload];
      });

    const first = performTokenRefresh();
    const second = performTokenRefresh();

    // Both calls should return the same promise instance
    expect(first).toBe(second);

    resolveRequest(undefined);
    await Promise.all([first, second]);

    // Only one POST was made
    const calls = mockAxios.history['post'] ?? [];
    expect(calls.filter((r) => r.url?.includes('/token/refresh'))).toHaveLength(1);
  });
});

// ---------------------------------------------------------------------------
// Response interceptor — 401 handling
// ---------------------------------------------------------------------------

describe('response interceptor', () => {
  it('retries the original request with a new token after a 401', async () => {
    localStorage.setItem('token', 'expired-token');
    localStorage.setItem('refresh_token', 'valid-refresh');

    // First call returns 401, second returns 200
    mockApi
      .onGet('/games/1')
      .replyOnce(401)
      .onGet('/games/1')
      .replyOnce(200, { id: '1' });

    mockAxios
      .onPost(`${BASE_URL}/token/refresh`)
      .reply(200, refreshPayload);

    const response = await api.get('/games/1');

    expect(response.status).toBe(200);
    expect(response.data).toEqual({ id: '1' });
  });

  it('does NOT retry a 401 on /login', async () => {
    mockApi.onPost('/login').reply(401, { message: 'Bad credentials' });

    await expect(api.post('/login', {})).rejects.toMatchObject({
      response: { status: 401 },
    });
  });

  it('does NOT retry a 401 on /token/refresh (prevents infinite loop)', async () => {
    mockApi.onPost('/token/refresh').reply(401, {});

    await expect(api.post('/token/refresh', {})).rejects.toMatchObject({
      response: { status: 401 },
    });
  });

  it('does NOT retry a 401 on /register', async () => {
    mockApi.onPost('/register').reply(401, {});

    await expect(api.post('/register', {})).rejects.toMatchObject({
      response: { status: 401 },
    });
  });

  it('does NOT retry a second time when _retry is already set', async () => {
    localStorage.setItem('refresh_token', 'valid-refresh');

    // Both calls return 401 — the retry would also fail
    mockApi.onGet('/games/1').reply(401);

    mockAxios
      .onPost(`${BASE_URL}/token/refresh`)
      .reply(200, refreshPayload);

    await expect(api.get('/games/1')).rejects.toMatchObject({
      response: { status: 401 },
    });
  });

  it('propagates non-401 errors without attempting a refresh', async () => {
    mockApi.onGet('/games/1').reply(500, { message: 'Server error' });

    await expect(api.get('/games/1')).rejects.toMatchObject({
      response: { status: 500 },
    });
  });
});

// ---------------------------------------------------------------------------
// setAuthFailureCallback
// ---------------------------------------------------------------------------

describe('setAuthFailureCallback', () => {
  it('invokes the registered callback when auth fails', async () => {
    const cb = vi.fn();
    setAuthFailureCallback(cb);

    // Trigger auth failure via a failed refresh
    await expect(performTokenRefresh()).rejects.toThrow();

    expect(cb).toHaveBeenCalledOnce();
  });

  it('does not throw when no callback is registered', async () => {
    setAuthFailureCallback(null);

    await expect(performTokenRefresh()).rejects.toThrow();
    // Should not throw anything other than the expected rejection
  });
});

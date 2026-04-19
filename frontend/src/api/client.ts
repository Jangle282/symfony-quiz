import axios from 'axios';
import type { InternalAxiosRequestConfig } from 'axios';
import type { RefreshResponse } from '../types';

interface RetriableRequestConfig extends InternalAxiosRequestConfig {
  _retry?: boolean;
}

const BASE_URL = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8080/api';

let authFailureCallback: (() => void) | null = null;

export function setAuthFailureCallback(cb: (() => void) | null) {
  authFailureCallback = cb;
}

function handleAuthFailure() {
  localStorage.removeItem('token');
  localStorage.removeItem('refresh_token');
  localStorage.removeItem('user');
  authFailureCallback?.();
}

export const api = axios.create({
  baseURL: BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: false,
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.set('Authorization', `Bearer ${token}`);
  }
  return config;
});

let refreshPromise: Promise<RefreshResponse> | null = null;

export function performTokenRefresh(): Promise<RefreshResponse> {
  if (refreshPromise) {
    return refreshPromise;
  }

  const refreshToken = localStorage.getItem('refresh_token');
  if (!refreshToken) {
    return Promise.reject(new Error('No refresh token available'));
  }

  refreshPromise = axios
    .post<RefreshResponse>(`${BASE_URL}/token/refresh`, {
      refresh_token: refreshToken,
    })
    .then(({ data }) => {
      localStorage.setItem('token', data.token);
      localStorage.setItem('refresh_token', data.refresh_token);
      localStorage.setItem('user', JSON.stringify(data.user));
      return data;
    })
    .catch((error) => {
      handleAuthFailure();
      throw error;
    })
    .finally(() => {
      refreshPromise = null;
    });

  return refreshPromise;
}

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config as RetriableRequestConfig | undefined;

    if (!originalRequest) {
      return Promise.reject(error);
    }

    if (
      error.response?.status === 401 &&
      !originalRequest._retry &&
      !originalRequest.url?.includes('/login') &&
      !originalRequest.url?.includes('/token/refresh') &&
      !originalRequest.url?.includes('/register')
    ) {
      originalRequest._retry = true;

      try {
        const data = await performTokenRefresh();
        originalRequest.headers.set('Authorization', `Bearer ${data.token}`);
        return api(originalRequest);
      } catch (refreshError) {
        return Promise.reject(refreshError);
      }
    }

    return Promise.reject(error);
  },
);

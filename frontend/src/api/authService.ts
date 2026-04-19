import { api } from './client';
import type { LoginResponse, RefreshResponse, RegisterResponse } from '../types';

export async function register(
  username: string,
  password: string,
): Promise<RegisterResponse> {
  const response = await api.post<RegisterResponse>('/register', {
    username,
    password,
  });
  return response.data;
}

export async function login(
  username: string,
  password: string,
): Promise<LoginResponse> {
  const response = await api.post<LoginResponse>('/login', {
    username,
    password,
  });
  return response.data;
}

export async function logout(): Promise<void> {
  await api.post('/logout');
}

export async function refreshToken(
  refresh_token: string,
): Promise<RefreshResponse> {
  const response = await api.post<RefreshResponse>('/token/refresh', {
    refresh_token,
  });
  return response.data;
}

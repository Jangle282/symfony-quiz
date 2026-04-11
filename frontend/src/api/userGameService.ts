import { api } from './client';
import type { JoinGameResponse } from '../types';

export async function joinGame(id: string): Promise<JoinGameResponse> {
  const response = await api.post<JoinGameResponse>(`/games/${id}/join`);
  return response.data;
}

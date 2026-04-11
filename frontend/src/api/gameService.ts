import { api } from './client';
import type {
  CompleteGameResponse,
  GameCreatedResponse,
  GameDetailsResponse,
  GameResultsResponse,
} from '../types';

export async function createGame(
  difficulty?: string,
  name?: string,
): Promise<GameCreatedResponse> {
  const response = await api.post<GameCreatedResponse>('/games', {
    difficulty,
    name,
  });
  return response.data;
}

export async function getGame(id: string): Promise<GameDetailsResponse> {
  const response = await api.get<GameDetailsResponse>(`/games/${id}`);
  return response.data;
}

export async function completeGame(id: string): Promise<CompleteGameResponse> {
  const response = await api.post<CompleteGameResponse>(
    `/games/${id}/complete`,
  );
  return response.data;
}

export async function getResults(id: string): Promise<GameResultsResponse> {
  const response = await api.get<GameResultsResponse>(`/games/${id}/results`);
  return response.data;
}

export async function deleteGame(id: string): Promise<void> {
  await api.delete(`/games/${id}`);
}

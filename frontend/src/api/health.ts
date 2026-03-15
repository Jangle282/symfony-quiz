import { api } from './client';

export type HealthResponse = {
  status: string;
  timestamp: string;
};

export async function fetchHealth(): Promise<HealthResponse> {
  const response = await api.get<HealthResponse>('/health');
  return response.data;
}

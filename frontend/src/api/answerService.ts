import { api } from './client';
import type { SelectAnswerResponse } from '../types';

export async function selectAnswer(
  gameId: string,
  roundId: string,
  questionId: string,
  answerId: string,
): Promise<SelectAnswerResponse> {
  const response = await api.post<SelectAnswerResponse>(
    `/games/${gameId}/rounds/${roundId}/questions/${questionId}/answers/${answerId}/select`,
  );
  return response.data;
}

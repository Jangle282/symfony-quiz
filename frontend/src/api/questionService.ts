import { api } from './client';
import type { QuestionNavigationResponse } from '../types';

export async function getNextQuestion(
  gameId: string,
  roundId: string,
  questionId: string,
): Promise<QuestionNavigationResponse> {
  const response = await api.get<QuestionNavigationResponse>(
    `/games/${gameId}/rounds/${roundId}/questions/${questionId}/next`,
  );
  return response.data;
}

export async function getPreviousQuestion(
  gameId: string,
  roundId: string,
  questionId: string,
): Promise<QuestionNavigationResponse> {
  const response = await api.get<QuestionNavigationResponse>(
    `/games/${gameId}/rounds/${roundId}/questions/${questionId}/previous`,
  );
  return response.data;
}

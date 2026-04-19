export interface User {
  id: string;
  username: string;
  createdAt: string;
  updatedAt: string;
}

export interface LoginResponse {
  token: string;
  refresh_token: string;
  user: User;
}

export interface RefreshResponse {
  token: string;
  refresh_token: string;
  user: User;
}

export type RegisterResponse = User;

export interface Answer {
  id: string;
  answer_text: string;
  user_selected?: boolean;
}

export interface Question {
  id: string;
  question_text: string;
  answers: Answer[];
}

export interface RoundSummary {
  id: string;
  round_number: number;
  category: string;
  total_questions: number;
  answered_questions: number;
}

export interface RoundInfo {
  id: string;
  round_number: number;
  category: string;
}

export interface GameCreatedResponse {
  id: string;
  name: string | null;
  difficulty: string;
  round: RoundInfo;
  first_question: Question | null;
}

export interface GameDetailsResponse {
  id: string;
  name: string | null;
  difficulty: string;
  total_score: number;
  started_at: string;
  completed_at: string | null;
  rounds: RoundSummary[];
  current_question: Question | null;
}

export interface QuestionResult {
  question_id: string;
  question_text: string;
  correct_answer: string | null;
  selected_answer: string | null;
  is_correct: boolean;
}

export interface GameResultsResponse {
  game_id: string;
  total_score: number;
  total_questions: number;
  questions: QuestionResult[];
}

export interface SelectAnswerResponse {
  message: string;
  question_id: string;
  selected_answer_id: string;
}

export interface CompleteGameResponse {
  message: string;
  game_id: string;
  total_score: number;
  completed_at: string;
}

export interface JoinGameResponse {
  message: string;
  game_id: string;
  role: string;
}

export interface UserGameSummary {
  id: string;
  role: string;
  joinedAt: string;
  createdBy: string;
  totalScore: number;
  startedAt: string;
  completedAt: string | null;
}

export interface UserProfileResponse {
  user: User;
  games: UserGameSummary[];
}

export interface UpdateUsernameResponse {
  user: User;
}

export interface UpdatePasswordResponse {
  message: string;
}

export interface QuestionNavigationResponse {
  question: Question | null;
}

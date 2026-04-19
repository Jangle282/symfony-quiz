import { api } from './client';
import type {
  UpdatePasswordResponse,
  UpdateUsernameResponse,
  UserProfileResponse,
} from '../types';

export async function getUser(id: string): Promise<UserProfileResponse> {
  const response = await api.get<UserProfileResponse>(`/user/${id}`);
  return response.data;
}

export async function updateUsername(
  id: string,
  username: string,
): Promise<UpdateUsernameResponse> {
  const response = await api.patch<UpdateUsernameResponse>(
    `/user/${id}/username`,
    { username },
  );
  return response.data;
}

export async function updatePassword(
  id: string,
  current_password: string,
  new_password: string,
): Promise<UpdatePasswordResponse> {
  const response = await api.patch<UpdatePasswordResponse>(
    `/user/${id}/password`,
    { current_password, new_password },
  );
  return response.data;
}

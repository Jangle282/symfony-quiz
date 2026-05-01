import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { deleteGame } from '../api/gameService';
import { getUser, updatePassword, updateUsername } from '../api/userService';
import ConfirmModal from '../components/common/ConfirmModal';
import GameHistory from '../components/profile/GameHistory';
import UpdatePasswordForm from '../components/profile/UpdatePasswordForm';
import UpdateUsernameForm from '../components/profile/UpdateUsernameForm';
import { useAuth } from '../hooks/useAuth';
import { extractApiErrorMessage } from '../utils/apiError';

export default function ProfilePage() {
  const { user, setUser } = useAuth();
  const queryClient = useQueryClient();

  const [usernameSuccess, setUsernameSuccess] = useState<string | null>(null);
  const [usernameError, setUsernameError] = useState<string | null>(null);
  const [passwordSuccess, setPasswordSuccess] = useState<string | null>(null);
  const [passwordError, setPasswordError] = useState<string | null>(null);
  const [deleteError, setDeleteError] = useState<string | null>(null);
  const [pendingDeleteGameId, setPendingDeleteGameId] = useState<string | null>(null);

  const profileQuery = useQuery({
    queryKey: ['user-profile', user?.id],
    queryFn: () => getUser(user!.id),
    enabled: !!user?.id,
  });

  const usernameMutation = useMutation({
    mutationFn: (username: string) => updateUsername(user!.id, username),
    onSuccess: (response) => {
      setUser(response.user);
      setUsernameError(null);
      setUsernameSuccess('Username updated successfully.');
      queryClient.invalidateQueries({ queryKey: ['user-profile', user?.id] });
    },
    onError: (error) => {
      setUsernameSuccess(null);
      setUsernameError(extractApiErrorMessage(error, 'Failed to update username.'));
    },
  });

  const passwordMutation = useMutation({
    mutationFn: ({ currentPassword, newPassword }: { currentPassword: string; newPassword: string }) =>
      updatePassword(user!.id, currentPassword, newPassword),
    onSuccess: () => {
      setPasswordError(null);
      setPasswordSuccess('Password updated successfully.');
    },
    onError: (error) => {
      setPasswordSuccess(null);
      setPasswordError(extractApiErrorMessage(error, 'Failed to update password.'));
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (gameId: string) => deleteGame(gameId),
    onSuccess: () => {
      setDeleteError(null);
      setPendingDeleteGameId(null);
      queryClient.invalidateQueries({ queryKey: ['user-profile', user?.id] });
    },
    onError: (error) => {
      setDeleteError(extractApiErrorMessage(error, 'Failed to delete game.'));
    },
  });

  if (!user) {
    return (
      <div className="container mx-auto p-8 max-w-4xl" data-cy="profile-error">
        <div className="alert alert-error">
          <span>User session is not available.</span>
        </div>
      </div>
    );
  }

  if (profileQuery.isLoading) {
    return (
      <div className="flex justify-center items-center min-h-[50vh]" data-cy="profile-loading">
        <span className="loading loading-spinner loading-lg" />
      </div>
    );
  }

  if (profileQuery.isError || !profileQuery.data) {
    return (
      <div className="container mx-auto p-8 max-w-4xl" data-cy="profile-error">
        <div className="alert alert-error mb-4">
          <span>{extractApiErrorMessage(profileQuery.error, 'Failed to load profile.')}</span>
        </div>
        <Link to="/lobby" className="btn btn-outline">
          Back to Lobby
        </Link>
      </div>
    );
  }

  const profile = profileQuery.data;

  return (
    <div className="container mx-auto p-8 max-w-4xl space-y-6" data-cy="profile-page">
      <header>
        <h1 className="text-3xl font-bold">Profile</h1>
        <p className="text-base-content/70 mt-1" data-cy="profile-username">
          Username: {profile.user.username}
        </p>
      </header>

      <div className="grid md:grid-cols-2 gap-4">
        <UpdateUsernameForm
          currentUsername={profile.user.username}
          isPending={usernameMutation.isPending}
          error={usernameError}
          success={usernameSuccess}
          onSubmit={async (username) => {
            setUsernameSuccess(null);
            setUsernameError(null);
            await usernameMutation.mutateAsync(username);
          }}
        />
        <UpdatePasswordForm
          isPending={passwordMutation.isPending}
          error={passwordError}
          success={passwordSuccess}
          onSubmit={async (currentPassword, newPassword) => {
            setPasswordSuccess(null);
            setPasswordError(null);
            await passwordMutation.mutateAsync({ currentPassword, newPassword });
          }}
        />
      </div>

      {deleteError && (
        <div className="alert alert-error" data-cy="profile-delete-error">
          <span>{deleteError}</span>
        </div>
      )}

      <GameHistory
        games={profile.games}
        deletingGameId={deleteMutation.isPending ? pendingDeleteGameId : null}
        onDeleteRequest={(gameId) => {
          setDeleteError(null);
          setPendingDeleteGameId(gameId);
        }}
      />

      <Link to="/lobby" className="btn btn-outline" data-cy="profile-back-lobby">
        Back to Lobby
      </Link>

      <ConfirmModal
        isOpen={!!pendingDeleteGameId}
        title="Delete game from history?"
        message="This permanently deletes the selected game."
        confirmLabel="Delete game"
        isConfirming={deleteMutation.isPending}
        onCancel={() => setPendingDeleteGameId(null)}
        onConfirm={() => {
          if (pendingDeleteGameId) {
            deleteMutation.mutate(pendingDeleteGameId);
          }
        }}
        dataCyPrefix="profile-delete-modal"
      />
    </div>
  );
}

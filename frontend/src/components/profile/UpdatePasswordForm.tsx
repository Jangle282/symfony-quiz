import { useState } from 'react';

interface UpdatePasswordFormProps {
  isPending: boolean;
  error: string | null;
  success: string | null;
  onSubmit: (currentPassword: string, newPassword: string) => Promise<void>;
}

export default function UpdatePasswordForm({
  isPending,
  error,
  success,
  onSubmit,
}: UpdatePasswordFormProps) {
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [validationError, setValidationError] = useState<string | null>(null);

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();

    if (!currentPassword || !newPassword) {
      setValidationError('Current password and new password are required.');
      return;
    }
    if (
      newPassword.length < 10 ||
      !/[A-Za-z]/.test(newPassword) ||
      !/\d/.test(newPassword) ||
      !/[^A-Za-z0-9]/.test(newPassword)
    ) {
      setValidationError('Password must be at least 10 characters and include letters, numbers, and symbols.');
      return;
    }

    setValidationError(null);
    try {
      await onSubmit(currentPassword, newPassword);
      setCurrentPassword('');
      setNewPassword('');
    } catch {
      // API errors are surfaced via parent state.
    }
  }

  return (
    <section className="card bg-base-200 shadow-sm" data-cy="update-password-card">
      <div className="card-body">
        <h2 className="card-title">Update Password</h2>

        <form className="space-y-3" onSubmit={handleSubmit} data-cy="update-password-form">
          <label className="form-control w-full">
            <span className="label-text mb-1">Current password</span>
            <input
              type="password"
              className="input input-bordered w-full"
              value={currentPassword}
              onChange={(e) => setCurrentPassword(e.target.value)}
              disabled={isPending}
              data-cy="current-password-input"
            />
          </label>

          <label className="form-control w-full">
            <span className="label-text mb-1">New password</span>
            <input
              type="password"
              className="input input-bordered w-full"
              value={newPassword}
              onChange={(e) => setNewPassword(e.target.value)}
              disabled={isPending}
              data-cy="new-password-input"
            />
          </label>

          {validationError && (
            <div className="alert alert-warning" data-cy="password-validation-error">
              <span>{validationError}</span>
            </div>
          )}

          {error && (
            <div className="alert alert-error" data-cy="password-api-error">
              <span>{error}</span>
            </div>
          )}

          {success && (
            <div className="alert alert-success" data-cy="password-success">
              <span>{success}</span>
            </div>
          )}

          <button type="submit" className="btn btn-primary" disabled={isPending} data-cy="password-submit">
            {isPending ? <span className="loading loading-spinner loading-sm" /> : 'Save password'}
          </button>
        </form>
      </div>
    </section>
  );
}

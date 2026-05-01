import { useEffect, useState } from 'react';

interface UpdateUsernameFormProps {
  currentUsername: string;
  isPending: boolean;
  error: string | null;
  success: string | null;
  onSubmit: (username: string) => Promise<void>;
}

export default function UpdateUsernameForm({
  currentUsername,
  isPending,
  error,
  success,
  onSubmit,
}: UpdateUsernameFormProps) {
  const [username, setUsername] = useState(currentUsername);
  const [validationError, setValidationError] = useState<string | null>(null);

  useEffect(() => {
    setUsername(currentUsername);
  }, [currentUsername]);

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();

    const trimmed = username.trim();
    if (!trimmed) {
      setValidationError('Username is required.');
      return;
    }
    if (trimmed === currentUsername) {
      setValidationError('Please choose a different username.');
      return;
    }

    setValidationError(null);
    try {
      await onSubmit(trimmed);
    } catch {
      // API errors are surfaced via parent state.
    }
  }

  return (
    <section className="card bg-base-200 shadow-sm" data-cy="update-username-card">
      <div className="card-body">
        <h2 className="card-title">Update Username</h2>

        <form className="space-y-3" onSubmit={handleSubmit} data-cy="update-username-form">
          <label className="form-control w-full">
            <span className="label-text mb-1">New username</span>
            <input
              className="input input-bordered w-full"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              disabled={isPending}
              data-cy="username-input"
            />
          </label>

          {validationError && (
            <div className="alert alert-warning" data-cy="username-validation-error">
              <span>{validationError}</span>
            </div>
          )}

          {error && (
            <div className="alert alert-error" data-cy="username-api-error">
              <span>{error}</span>
            </div>
          )}

          {success && (
            <div className="alert alert-success" data-cy="username-success">
              <span>{success}</span>
            </div>
          )}

          <button type="submit" className="btn btn-primary" disabled={isPending} data-cy="username-submit">
            {isPending ? <span className="loading loading-spinner loading-sm" /> : 'Save username'}
          </button>
        </form>
      </div>
    </section>
  );
}

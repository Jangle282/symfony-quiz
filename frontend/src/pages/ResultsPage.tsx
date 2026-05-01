import { useState } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
import { Link, useNavigate, useParams } from 'react-router-dom';
import axios from 'axios';
import { deleteGame, getResults } from '../api/gameService';
import QuestionBreakdown from '../components/game/QuestionBreakdown';
import ConfirmModal from '../components/common/ConfirmModal';

function extractErrorMessage(error: unknown, fallback: string): string {
  if (axios.isAxiosError(error)) {
    const responseError = error.response?.data?.error;
    if (typeof responseError === 'string' && responseError.trim()) {
      return responseError;
    }
  }
  return fallback;
}

export default function ResultsPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);

  const {
    data,
    isLoading,
    isError,
    error,
  } = useQuery({
    queryKey: ['game-results', id],
    queryFn: () => getResults(id!),
    enabled: !!id,
  });

  const deleteMutation = useMutation({
    mutationFn: (gameId: string) => deleteGame(gameId),
    onSuccess: () => {
      navigate('/lobby');
    },
  });

  if (!id) {
    return (
      <div className="container mx-auto p-8 max-w-3xl" data-cy="results-error">
        <div className="alert alert-error">
          <span>Game ID is missing.</span>
        </div>
      </div>
    );
  }

  if (isLoading) {
    return (
      <div className="flex justify-center items-center min-h-[50vh]" data-cy="results-loading">
        <span className="loading loading-spinner loading-lg" />
      </div>
    );
  }

  if (isError || !data) {
    return (
      <div className="container mx-auto p-8 max-w-3xl" data-cy="results-error">
        <div className="alert alert-error mb-4">
          <span>{extractErrorMessage(error, 'Failed to load game results.')}</span>
        </div>
        <Link to="/lobby" className="btn btn-outline">
          Back to Lobby
        </Link>
      </div>
    );
  }

  const percentage = data.total_questions > 0
    ? Math.round((data.total_score / data.total_questions) * 100)
    : 0;

  return (
    <div className="container mx-auto p-8 max-w-4xl" data-cy="results-page">
      <header className="mb-6">
        <h1 className="text-3xl font-bold">Game Results</h1>
        <p className="text-base-content/70 mt-1">Review your score and detailed answers.</p>
      </header>

      <div className="grid sm:grid-cols-2 gap-3 mb-6">
        <section className="card bg-base-200 shadow-sm" data-cy="results-score-card">
          <div className="card-body">
            <h2 className="card-title text-base">Total Score</h2>
            <p className="text-3xl font-bold" data-cy="results-score">
              {data.total_score} / {data.total_questions}
            </p>
          </div>
        </section>
        <section className="card bg-base-200 shadow-sm" data-cy="results-percentage-card">
          <div className="card-body">
            <h2 className="card-title text-base">Percentage</h2>
            <p className="text-3xl font-bold" data-cy="results-percentage">
              {percentage}%
            </p>
          </div>
        </section>
      </div>

      <section className="mb-6">
        <h2 className="text-xl font-semibold mb-3">Question Breakdown</h2>
        <QuestionBreakdown questions={data.questions} />
      </section>

      {deleteMutation.isError && (
        <div className="alert alert-error mb-4" data-cy="results-delete-error">
          <span>{extractErrorMessage(deleteMutation.error, 'Failed to delete game.')}</span>
        </div>
      )}

      <div className="flex flex-wrap gap-2">
        <Link to="/lobby" className="btn btn-outline" data-cy="results-back-lobby">
          Back to Lobby
        </Link>
        <button
          type="button"
          className="btn btn-error"
          onClick={() => setIsDeleteModalOpen(true)}
          data-cy="btn-show-delete-modal"
        >
          Delete game
        </button>
      </div>

      <ConfirmModal
        isOpen={isDeleteModalOpen}
        title="Delete this game?"
        message="This action is permanent and cannot be undone."
        confirmLabel="Delete game"
        isConfirming={deleteMutation.isPending}
        onCancel={() => setIsDeleteModalOpen(false)}
        onConfirm={() => deleteMutation.mutate(id)}
        dataCyPrefix="results-delete-modal"
      />
    </div>
  );
}

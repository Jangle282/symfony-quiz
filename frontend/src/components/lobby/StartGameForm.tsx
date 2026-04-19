import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { createGame } from '../../api/gameService';
import axios from 'axios';

export default function StartGameForm() {
  const navigate = useNavigate();
  const [difficulty, setDifficulty] = useState('medium');
  const [name, setName] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setLoading(true);

    try {
      const game = await createGame(difficulty, name || undefined);
      navigate(`/game/${game.id}`);
    } catch (err) {
      if (axios.isAxiosError(err) && err.response?.data?.error) {
        setError(err.response.data.error);
      } else {
        setError('Failed to create game. Please try again.');
      }
    } finally {
      setLoading(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="card bg-base-200 shadow-md">
      <div className="card-body gap-4">
        <h2 className="card-title">Start a New Game</h2>

        <div className="form-control w-full">
          <label className="label" htmlFor="game-name">
            <span className="label-text">Game Name (optional)</span>
          </label>
          <input
            id="game-name"
            type="text"
            placeholder="e.g. Friday Quiz"
            className="input input-bordered w-full"
            value={name}
            onChange={(e) => setName(e.target.value)}
            disabled={loading}
          />
        </div>

        <div className="form-control w-full">
          <label className="label" htmlFor="difficulty">
            <span className="label-text">Difficulty</span>
          </label>
          <select
            id="difficulty"
            className="select select-bordered w-full"
            value={difficulty}
            onChange={(e) => setDifficulty(e.target.value)}
            disabled={loading}
          >
            <option value="easy">Easy</option>
            <option value="medium">Medium</option>
            <option value="hard">Hard</option>
          </select>
        </div>

        {error && (
          <div className="alert alert-error">
            <span>{error}</span>
          </div>
        )}

        <div className="card-actions justify-end">
          <button
            type="submit"
            className="btn btn-primary"
            disabled={loading}
          >
            {loading && <span className="loading loading-spinner loading-sm" />}
            Start Game
          </button>
        </div>
      </div>
    </form>
  );
}

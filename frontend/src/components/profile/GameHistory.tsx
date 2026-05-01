import type { UserGameSummary } from '../../types';

interface GameHistoryProps {
  games: UserGameSummary[];
  deletingGameId: string | null;
  onDeleteRequest: (gameId: string) => void;
}

function formatDate(value: string): string {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return value;
  }
  return date.toLocaleString();
}

export default function GameHistory({
  games,
  deletingGameId,
  onDeleteRequest,
}: GameHistoryProps) {
  const completedGames = games
    .filter((game) => game.completedAt)
    .sort((a, b) => new Date(b.completedAt ?? '').getTime() - new Date(a.completedAt ?? '').getTime());

  return (
    <section className="card bg-base-200 shadow-sm" data-cy="game-history-card">
      <div className="card-body">
        <h2 className="card-title">Completed Games</h2>

        {completedGames.length === 0 ? (
          <p className="text-sm text-base-content/70" data-cy="game-history-empty">
            No completed games yet.
          </p>
        ) : (
          <ul className="space-y-2" data-cy="game-history-list">
            {completedGames.map((game) => (
              <li key={game.id} className="bg-base-100 border border-base-300 rounded-lg p-3" data-cy="game-history-item">
                <div className="flex items-center justify-between gap-2">
                  <div>
                    <p className="font-semibold">Game {game.id.slice(0, 8)}</p>
                    <p className="text-sm text-base-content/70">
                      Completed: {formatDate(game.completedAt ?? game.startedAt)}
                    </p>
                    <p className="text-sm" data-cy="game-history-score">
                      Score: {game.totalScore}
                    </p>
                  </div>
                  <button
                    type="button"
                    className="btn btn-outline btn-error btn-sm"
                    disabled={deletingGameId === game.id}
                    onClick={() => onDeleteRequest(game.id)}
                    data-cy={`game-history-delete-${game.id}`}
                  >
                    Delete
                  </button>
                </div>
              </li>
            ))}
          </ul>
        )}
      </div>
    </section>
  );
}

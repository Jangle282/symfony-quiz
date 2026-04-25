interface GameHeaderProps {
  name: string | null;
  difficulty: string;
  roundNumber: number;
  category: string;
  questionNumber: number;
  totalQuestions: number;
}

export default function GameHeader({
  name,
  difficulty,
  roundNumber,
  category,
  questionNumber,
  totalQuestions,
}: GameHeaderProps) {
  return (
    <div className="flex flex-wrap items-center justify-between gap-2 mb-6">
      <div>
        <h1 className="text-2xl font-bold" data-cy="game-name">
          {name || 'Quiz Game'}
        </h1>
        <p className="text-base-content/60 text-sm" data-cy="game-meta">
          Round {roundNumber} &middot; {category} &middot;{' '}
          <span className="capitalize">{difficulty}</span>
        </p>
      </div>
      <div className="badge badge-lg badge-outline" data-cy="question-counter">
        Question {questionNumber} / {totalQuestions}
      </div>
    </div>
  );
}

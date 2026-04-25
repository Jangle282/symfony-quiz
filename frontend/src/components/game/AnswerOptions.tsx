import type { Answer } from '../../types';

interface AnswerOptionsProps {
  answers: Answer[];
  selectedAnswerId: string | null;
  submittedAnswerId: string | null;
  onSelect: (answerId: string) => void;
  disabled: boolean;
}

export default function AnswerOptions({
  answers,
  selectedAnswerId,
  submittedAnswerId,
  onSelect,
  disabled,
}: AnswerOptionsProps) {
  function getButtonClass(answer: Answer): string {
    const base = 'btn btn-block justify-start text-left h-auto py-3 whitespace-normal';

    const isSelected = answer.id === selectedAnswerId;
    const isSubmitted = answer.id === submittedAnswerId;

    if (isSubmitted && isSelected) {
      return `${base} btn-primary`;
    }
    if (isSubmitted && !isSelected) {
      return `${base} btn-secondary btn-outline`;
    }
    if (!isSubmitted && isSelected) {
      return `${base} btn-accent btn-outline`;
    }
    return `${base} btn-ghost border border-base-300`;
  }

  return (
    <div className="grid gap-3" data-cy="answer-options">
      {answers.map((answer) => (
        <button
          key={answer.id}
          type="button"
          className={getButtonClass(answer)}
          onClick={() => onSelect(answer.id)}
          disabled={disabled}
          data-cy="answer-option"
          data-answer-id={answer.id}
          data-selected={answer.id === selectedAnswerId || undefined}
          data-submitted={answer.id === submittedAnswerId || undefined}
        >
          {answer.answer_text}
        </button>
      ))}
    </div>
  );
}

import type { Question } from '../../types';
import AnswerOptions from './AnswerOptions';

interface QuestionCardProps {
  question: Question;
  selectedAnswerId: string | null;
  submittedAnswerId: string | null;
  onSelectAnswer: (answerId: string) => void;
  disabled: boolean;
}

export default function QuestionCard({
  question,
  selectedAnswerId,
  submittedAnswerId,
  onSelectAnswer,
  disabled,
}: QuestionCardProps) {
  return (
    <div className="card bg-base-200 shadow-md mb-6" data-cy="question-card">
      <div className="card-body">
        <h2 className="card-title text-lg" data-cy="question-text">
          {question.question_text}
        </h2>
        <div className="mt-4">
          <AnswerOptions
            answers={question.answers}
            selectedAnswerId={selectedAnswerId}
            submittedAnswerId={submittedAnswerId}
            onSelect={onSelectAnswer}
            disabled={disabled}
          />
        </div>
      </div>
    </div>
  );
}

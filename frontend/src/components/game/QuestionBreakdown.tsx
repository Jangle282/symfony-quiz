import type { QuestionResult } from '../../types';

interface QuestionBreakdownProps {
  questions: QuestionResult[];
}

export default function QuestionBreakdown({ questions }: QuestionBreakdownProps) {
  if (questions.length === 0) {
    return (
      <div className="alert" data-cy="question-breakdown-empty">
        <span>No questions found for this game.</span>
      </div>
    );
  }

  return (
    <div className="space-y-3" data-cy="question-breakdown">
      {questions.map((question, index) => (
        <article key={question.question_id} className="card bg-base-200 shadow-sm" data-cy="question-breakdown-item">
          <div className="card-body gap-2">
            <div className="flex items-center justify-between gap-2">
              <h3 className="card-title text-base">Question {index + 1}</h3>
              <span className={`badge ${question.is_correct ? 'badge-success' : 'badge-error'}`} data-cy="question-breakdown-status">
                {question.is_correct ? 'Correct' : 'Incorrect'}
              </span>
            </div>

            <p className="font-medium" data-cy="question-breakdown-text">{question.question_text}</p>

            <div className="text-sm">
              <p data-cy="question-breakdown-selected">
                <span className="font-semibold">Your answer:</span>{' '}
                {question.selected_answer ?? 'No answer selected'}
              </p>
              <p data-cy="question-breakdown-correct">
                <span className="font-semibold">Correct answer:</span>{' '}
                {question.correct_answer ?? 'No correct answer provided'}
              </p>
            </div>
          </div>
        </article>
      ))}
    </div>
  );
}

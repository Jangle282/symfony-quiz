interface QuestionNavigationProps {
  onPrevious: () => void;
  onNext: () => void;
  onSubmit: () => void;
  onViewResults: () => void;
  hasPrevious: boolean;
  isLastQuestion: boolean;
  hasSubmittedAnswer: boolean;
  hasSelectedAnswer: boolean;
  isSubmitting: boolean;
  isNavigating: boolean;
  needsSubmit: boolean;
}

export default function QuestionNavigation({
  onPrevious,
  onNext,
  onSubmit,
  onViewResults,
  hasPrevious,
  isLastQuestion,
  hasSubmittedAnswer,
  hasSelectedAnswer,
  isSubmitting,
  isNavigating,
  needsSubmit,
}: QuestionNavigationProps) {
  return (
    <div className="flex justify-between items-center" data-cy="question-navigation">
      <button
        type="button"
        className="btn btn-outline"
        onClick={onPrevious}
        disabled={!hasPrevious || isNavigating || isSubmitting}
        data-cy="btn-previous"
      >
        Previous
      </button>

      <div className="flex gap-2">
        {needsSubmit && (
          <button
            type="button"
            className="btn btn-secondary"
            onClick={onSubmit}
            disabled={!hasSelectedAnswer || isSubmitting || isNavigating}
            data-cy="btn-submit-answer"
          >
            {isSubmitting ? (
              <span className="loading loading-spinner loading-sm" />
            ) : (
              'Submit Answer'
            )}
          </button>
        )}

        {isLastQuestion ? (
          <button
            type="button"
            className="btn btn-primary"
            onClick={onViewResults}
            disabled={!hasSubmittedAnswer || isNavigating || isSubmitting}
            data-cy="btn-view-results"
          >
            {isNavigating ? (
              <span className="loading loading-spinner loading-sm" />
            ) : (
              'View Results'
            )}
          </button>
        ) : (
          <button
            type="button"
            className="btn btn-primary"
            onClick={onNext}
            disabled={!hasSubmittedAnswer || isNavigating || isSubmitting}
            data-cy="btn-next"
          >
            {isNavigating ? (
              <span className="loading loading-spinner loading-sm" />
            ) : (
              'Next'
            )}
          </button>
        )}
      </div>
    </div>
  );
}

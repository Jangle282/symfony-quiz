import QuestionNavigation from '../../src/components/game/QuestionNavigation';

describe('QuestionNavigation', () => {
  const defaultProps = {
    onPrevious: () => {},
    onNext: () => {},
    onSubmit: () => {},
    onViewResults: () => {},
    hasPrevious: true,
    isLastQuestion: false,
    hasSubmittedAnswer: true,
    hasSelectedAnswer: true,
    isSubmitting: false,
    isNavigating: false,
    needsSubmit: false,
  };

  beforeEach(() => {
    defaultProps.onPrevious = cy.stub().as('onPrevious');
    defaultProps.onNext = cy.stub().as('onNext');
    defaultProps.onSubmit = cy.stub().as('onSubmit');
    defaultProps.onViewResults = cy.stub().as('onViewResults');
  });

  it('renders Previous and Next buttons', () => {
    cy.mount(<QuestionNavigation {...defaultProps} />);
    cy.get('[data-cy="btn-previous"]').should('exist');
    cy.get('[data-cy="btn-next"]').should('exist');
  });

  it('disables Previous when hasPrevious is false', () => {
    cy.mount(<QuestionNavigation {...defaultProps} hasPrevious={false} />);
    cy.get('[data-cy="btn-previous"]').should('be.disabled');
  });

  it('enables Previous when hasPrevious is true', () => {
    cy.mount(<QuestionNavigation {...defaultProps} />);
    cy.get('[data-cy="btn-previous"]').should('not.be.disabled');
  });

  it('disables Next when no submitted answer', () => {
    cy.mount(
      <QuestionNavigation {...defaultProps} hasSubmittedAnswer={false} />,
    );
    cy.get('[data-cy="btn-next"]').should('be.disabled');
  });

  it('calls onNext when Next is clicked', () => {
    cy.mount(<QuestionNavigation {...defaultProps} />);
    cy.get('[data-cy="btn-next"]').click();
    cy.get('@onNext').should('have.been.calledOnce');
  });

  it('calls onPrevious when Previous is clicked', () => {
    cy.mount(<QuestionNavigation {...defaultProps} />);
    cy.get('[data-cy="btn-previous"]').click();
    cy.get('@onPrevious').should('have.been.calledOnce');
  });

  it('shows Submit Answer button when needsSubmit is true', () => {
    cy.mount(<QuestionNavigation {...defaultProps} needsSubmit={true} />);
    cy.get('[data-cy="btn-submit-answer"]').should('exist');
  });

  it('hides Submit Answer button when needsSubmit is false', () => {
    cy.mount(<QuestionNavigation {...defaultProps} needsSubmit={false} />);
    cy.get('[data-cy="btn-submit-answer"]').should('not.exist');
  });

  it('disables Submit Answer when no answer is selected', () => {
    cy.mount(
      <QuestionNavigation
        {...defaultProps}
        needsSubmit={true}
        hasSelectedAnswer={false}
      />,
    );
    cy.get('[data-cy="btn-submit-answer"]').should('be.disabled');
  });

  it('calls onSubmit when Submit Answer is clicked', () => {
    cy.mount(<QuestionNavigation {...defaultProps} needsSubmit={true} />);
    cy.get('[data-cy="btn-submit-answer"]').click();
    cy.get('@onSubmit').should('have.been.calledOnce');
  });

  it('shows View Results on last question instead of Next', () => {
    cy.mount(<QuestionNavigation {...defaultProps} isLastQuestion={true} />);
    cy.get('[data-cy="btn-next"]').should('not.exist');
    cy.get('[data-cy="btn-view-results"]').should('exist');
  });

  it('disables View Results when no submitted answer', () => {
    cy.mount(
      <QuestionNavigation
        {...defaultProps}
        isLastQuestion={true}
        hasSubmittedAnswer={false}
      />,
    );
    cy.get('[data-cy="btn-view-results"]').should('be.disabled');
  });

  it('calls onViewResults when View Results is clicked', () => {
    cy.mount(<QuestionNavigation {...defaultProps} isLastQuestion={true} />);
    cy.get('[data-cy="btn-view-results"]').click();
    cy.get('@onViewResults').should('have.been.calledOnce');
  });

  it('disables all buttons when isSubmitting', () => {
    cy.mount(
      <QuestionNavigation
        {...defaultProps}
        needsSubmit={true}
        isSubmitting={true}
      />,
    );
    cy.get('[data-cy="btn-previous"]').should('be.disabled');
    cy.get('[data-cy="btn-next"]').should('be.disabled');
    cy.get('[data-cy="btn-submit-answer"]').should('be.disabled');
  });

  it('disables all buttons when isNavigating', () => {
    cy.mount(<QuestionNavigation {...defaultProps} isNavigating={true} />);
    cy.get('[data-cy="btn-previous"]').should('be.disabled');
    cy.get('[data-cy="btn-next"]').should('be.disabled');
  });
});

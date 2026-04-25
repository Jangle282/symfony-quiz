import AnswerOptions from '../../src/components/game/AnswerOptions';
import type { Answer } from '../../src/types';

describe('AnswerOptions', () => {
  const answers: Answer[] = [
    { id: 'a1', answer_text: 'Paris' },
    { id: 'a2', answer_text: 'London' },
    { id: 'a3', answer_text: 'Berlin' },
    { id: 'a4', answer_text: 'Madrid' },
  ];

  it('renders all four answer options', () => {
    cy.mount(
      <AnswerOptions
        answers={answers}
        selectedAnswerId={null}
        submittedAnswerId={null}
        onSelect={cy.stub()}
        disabled={false}
      />,
    );
    cy.get('[data-cy="answer-option"]').should('have.length', 4);
    cy.get('[data-cy="answer-option"]').eq(0).should('contain.text', 'Paris');
    cy.get('[data-cy="answer-option"]').eq(1).should('contain.text', 'London');
    cy.get('[data-cy="answer-option"]').eq(2).should('contain.text', 'Berlin');
    cy.get('[data-cy="answer-option"]').eq(3).should('contain.text', 'Madrid');
  });

  it('calls onSelect when an answer is clicked', () => {
    const onSelect = cy.stub().as('onSelect');
    cy.mount(
      <AnswerOptions
        answers={answers}
        selectedAnswerId={null}
        submittedAnswerId={null}
        onSelect={onSelect}
        disabled={false}
      />,
    );
    cy.get('[data-cy="answer-option"]').eq(1).click();
    cy.get('@onSelect').should('have.been.calledWith', 'a2');
  });

  it('marks selected answer with data-selected attribute', () => {
    cy.mount(
      <AnswerOptions
        answers={answers}
        selectedAnswerId="a2"
        submittedAnswerId={null}
        onSelect={cy.stub()}
        disabled={false}
      />,
    );
    cy.get('[data-answer-id="a2"]').should('have.attr', 'data-selected');
    cy.get('[data-answer-id="a1"]').should('not.have.attr', 'data-selected');
  });

  it('marks submitted answer with data-submitted attribute', () => {
    cy.mount(
      <AnswerOptions
        answers={answers}
        selectedAnswerId="a3"
        submittedAnswerId="a3"
        onSelect={cy.stub()}
        disabled={false}
      />,
    );
    cy.get('[data-answer-id="a3"]').should('have.attr', 'data-submitted');
    cy.get('[data-answer-id="a1"]').should('not.have.attr', 'data-submitted');
  });

  it('shows submitted+deselected state when selection changed after submit', () => {
    cy.mount(
      <AnswerOptions
        answers={answers}
        selectedAnswerId="a1"
        submittedAnswerId="a3"
        onSelect={cy.stub()}
        disabled={false}
      />,
    );
    // a3 is submitted but not currently selected
    cy.get('[data-answer-id="a3"]').should('have.attr', 'data-submitted');
    cy.get('[data-answer-id="a3"]').should('not.have.attr', 'data-selected');
    // a1 is selected but not submitted
    cy.get('[data-answer-id="a1"]').should('have.attr', 'data-selected');
    cy.get('[data-answer-id="a1"]').should('not.have.attr', 'data-submitted');
  });

  it('disables all buttons when disabled prop is true', () => {
    cy.mount(
      <AnswerOptions
        answers={answers}
        selectedAnswerId={null}
        submittedAnswerId={null}
        onSelect={cy.stub()}
        disabled={true}
      />,
    );
    cy.get('[data-cy="answer-option"]').each(($btn) => {
      cy.wrap($btn).should('be.disabled');
    });
  });
});

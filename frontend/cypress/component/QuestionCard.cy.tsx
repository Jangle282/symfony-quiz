import QuestionCard from '../../src/components/game/QuestionCard';
import type { Question } from '../../src/types';

describe('QuestionCard', () => {
  const question: Question = {
    id: 'q1',
    question_text: 'What is the capital of France?',
    answers: [
      { id: 'a1', answer_text: 'Paris' },
      { id: 'a2', answer_text: 'London' },
      { id: 'a3', answer_text: 'Berlin' },
      { id: 'a4', answer_text: 'Madrid' },
    ],
  };

  it('renders question text', () => {
    cy.mount(
      <QuestionCard
        question={question}
        selectedAnswerId={null}
        submittedAnswerId={null}
        onSelectAnswer={cy.stub()}
        disabled={false}
      />,
    );
    cy.get('[data-cy="question-text"]').should(
      'contain.text',
      'What is the capital of France?',
    );
  });

  it('renders answer options inside the card', () => {
    cy.mount(
      <QuestionCard
        question={question}
        selectedAnswerId={null}
        submittedAnswerId={null}
        onSelectAnswer={cy.stub()}
        disabled={false}
      />,
    );
    cy.get('[data-cy="question-card"]')
      .find('[data-cy="answer-options"]')
      .should('exist');
    cy.get('[data-cy="answer-option"]').should('have.length', 4);
  });

  it('passes selected answer to AnswerOptions', () => {
    cy.mount(
      <QuestionCard
        question={question}
        selectedAnswerId="a2"
        submittedAnswerId={null}
        onSelectAnswer={cy.stub()}
        disabled={false}
      />,
    );
    cy.get('[data-answer-id="a2"]').should('have.attr', 'data-selected');
  });

  it('calls onSelectAnswer when an answer is clicked', () => {
    const onSelectAnswer = cy.stub().as('onSelectAnswer');
    cy.mount(
      <QuestionCard
        question={question}
        selectedAnswerId={null}
        submittedAnswerId={null}
        onSelectAnswer={onSelectAnswer}
        disabled={false}
      />,
    );
    cy.get('[data-cy="answer-option"]').eq(0).click();
    cy.get('@onSelectAnswer').should('have.been.calledWith', 'a1');
  });
});

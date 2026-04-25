import GameHeader from '../../src/components/game/GameHeader';

describe('GameHeader', () => {
  const defaultProps = {
    name: 'Friday Quiz',
    difficulty: 'medium',
    roundNumber: 1,
    category: 'General Knowledge',
    questionNumber: 3,
    totalQuestions: 10,
  };

  it('renders game name', () => {
    cy.mount(<GameHeader {...defaultProps} />);
    cy.get('[data-cy="game-name"]').should('contain.text', 'Friday Quiz');
  });

  it('renders fallback name when name is null', () => {
    cy.mount(<GameHeader {...defaultProps} name={null} />);
    cy.get('[data-cy="game-name"]').should('contain.text', 'Quiz Game');
  });

  it('renders round, category, and difficulty', () => {
    cy.mount(<GameHeader {...defaultProps} />);
    cy.get('[data-cy="game-meta"]')
      .should('contain.text', 'Round 1')
      .and('contain.text', 'General Knowledge')
      .and('contain.text', 'medium');
  });

  it('renders question counter', () => {
    cy.mount(<GameHeader {...defaultProps} />);
    cy.get('[data-cy="question-counter"]').should(
      'contain.text',
      'Question 3 / 10',
    );
  });
});

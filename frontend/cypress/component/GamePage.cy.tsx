import { MemoryRouter, Route, Routes } from 'react-router-dom';
import GamePage from '../../src/pages/GamePage';
import type { GameDetailsResponse } from '../../src/types';

const mockGame: GameDetailsResponse = {
  id: 'game-1',
  name: 'Friday Quiz',
  difficulty: 'medium',
  total_score: 0,
  started_at: '2026-04-20T12:00:00Z',
  completed_at: null,
  rounds: [
    {
      id: 'round-1',
      round_number: 1,
      category: 'General Knowledge',
      total_questions: 3,
      answered_questions: 0,
    },
  ],
  current_question: {
    id: 'q1',
    question_text: 'What is 2 + 2?',
    answers: [
      { id: 'a1', answer_text: '3' },
      { id: 'a2', answer_text: '4' },
      { id: 'a3', answer_text: '5' },
      { id: 'a4', answer_text: '6' },
    ],
  },
};

function mountGamePage() {
  window.localStorage.setItem('token', 'test-token');

  cy.mount(
    <MemoryRouter initialEntries={['/game/game-1']}>
      <Routes>
        <Route path="/game/:id" element={<GamePage />} />
        <Route path="/results/:id" element={<div data-cy="results-page">Results</div>} />
      </Routes>
    </MemoryRouter>,
  );
}

describe('GamePage', () => {
  beforeEach(() => {
    cy.intercept('GET', '**/api/games/game-1', mockGame).as('getGame');
  });

  it('shows loading spinner initially', () => {
    cy.intercept('GET', '**/api/games/game-1', () => {
      // Keep request pending to assert loading UI.
    }).as('getGamePending');
    mountGamePage();
    cy.get('[data-cy="game-loading"]').should('exist');
  });

  it('renders game header with correct info', () => {
    mountGamePage();
    cy.wait('@getGame');
    cy.get('[data-cy="game-name"]').should('contain.text', 'Friday Quiz');
    cy.get('[data-cy="game-meta"]')
      .should('contain.text', 'Round 1')
      .and('contain.text', 'General Knowledge')
      .and('contain.text', 'medium');
    cy.get('[data-cy="question-counter"]').should('contain.text', 'Question 1 / 3');
  });

  it('renders question text and answer options', () => {
    mountGamePage();
    cy.wait('@getGame');
    cy.get('[data-cy="question-text"]').should('contain.text', 'What is 2 + 2?');
    cy.get('[data-cy="answer-option"]').should('have.length', 4);
  });

  it('allows selecting an answer', () => {
    mountGamePage();
    cy.wait('@getGame');
    cy.get('[data-answer-id="a2"]').click();
    cy.get('[data-answer-id="a2"]').should('have.attr', 'data-selected');
  });

  it('shows Submit Answer button after selecting a different answer', () => {
    mountGamePage();
    cy.wait('@getGame');
    cy.get('[data-cy="btn-submit-answer"]').should('not.exist');
    cy.get('[data-answer-id="a2"]').click();
    cy.get('[data-cy="btn-submit-answer"]').should('exist');
  });

  it('submits answer and enables Next', () => {
    cy.intercept('POST', '**/api/games/game-1/rounds/round-1/questions/q1/answers/a2/select', {
      message: 'Answer selected.',
      question_id: 'q1',
      selected_answer_id: 'a2',
    }).as('submitAnswer');

    mountGamePage();
    cy.wait('@getGame');
    cy.get('[data-cy="btn-next"]').should('be.disabled');
    cy.get('[data-answer-id="a2"]').click();
    cy.get('[data-cy="btn-submit-answer"]').click();
    cy.wait('@submitAnswer');
    cy.get('[data-cy="btn-next"]').should('not.be.disabled');
  });

  it('navigates to next question', () => {
    cy.intercept('POST', '**/api/games/game-1/rounds/round-1/questions/q1/answers/a2/select', {
      message: 'Answer selected.',
      question_id: 'q1',
      selected_answer_id: 'a2',
    }).as('submitAnswer');

    cy.intercept('GET', '**/api/games/game-1/rounds/round-1/questions/q1/next', {
      question: {
        id: 'q2',
        question_text: 'What is the capital of France?',
        answers: [
          { id: 'b1', answer_text: 'Paris' },
          { id: 'b2', answer_text: 'London' },
          { id: 'b3', answer_text: 'Berlin' },
          { id: 'b4', answer_text: 'Madrid' },
        ],
      },
    }).as('nextQuestion');

    mountGamePage();
    cy.wait('@getGame');
    cy.get('[data-answer-id="a2"]').click();
    cy.get('[data-cy="btn-submit-answer"]').click();
    cy.wait('@submitAnswer');
    cy.get('[data-cy="btn-next"]').click();
    cy.wait('@nextQuestion');

    cy.get('[data-cy="question-text"]').should('contain.text', 'What is the capital of France?');
    cy.get('[data-cy="question-counter"]').should('contain.text', 'Question 2 / 3');
  });

  it('navigates to previous question', () => {
    const gameOnQ2: GameDetailsResponse = {
      ...mockGame,
      rounds: [{ ...mockGame.rounds[0], answered_questions: 1 }],
      current_question: {
        id: 'q2',
        question_text: 'Second question?',
        answers: [
          { id: 'b1', answer_text: 'A' },
          { id: 'b2', answer_text: 'B', user_selected: true },
          { id: 'b3', answer_text: 'C' },
          { id: 'b4', answer_text: 'D' },
        ],
      },
    };

    cy.intercept('GET', '**/api/games/game-1', gameOnQ2).as('getGameQ2');
    cy.intercept('GET', '**/api/games/game-1/rounds/round-1/questions/q2/previous', {
      question: {
        id: 'q1',
        question_text: 'First question?',
        answers: [
          { id: 'a1', answer_text: 'X', user_selected: true },
          { id: 'a2', answer_text: 'Y' },
          { id: 'a3', answer_text: 'Z' },
          { id: 'a4', answer_text: 'W' },
        ],
      },
    }).as('previousQuestion');

    mountGamePage();
    cy.wait('@getGameQ2');
    cy.get('[data-cy="btn-previous"]').click();
    cy.wait('@previousQuestion');
    cy.get('[data-cy="question-text"]').should('contain.text', 'First question?');
  });

  it('shows View Results on last question and navigates to results', () => {
    const lastQuestionGame: GameDetailsResponse = {
      ...mockGame,
      rounds: [{ ...mockGame.rounds[0], answered_questions: 2 }],
      current_question: {
        id: 'q3',
        question_text: 'Last question?',
        answers: [
          { id: 'c1', answer_text: 'A' },
          { id: 'c2', answer_text: 'B' },
          { id: 'c3', answer_text: 'C' },
          { id: 'c4', answer_text: 'D' },
        ],
      },
    };

    cy.intercept('GET', '**/api/games/game-1', lastQuestionGame).as('getLastGame');
    cy.intercept('POST', '**/api/games/game-1/rounds/round-1/questions/q3/answers/c1/select', {
      message: 'Answer selected.',
      question_id: 'q3',
      selected_answer_id: 'c1',
    }).as('submitLastAnswer');
    cy.intercept('POST', '**/api/games/game-1/complete', {
      message: 'Game completed.',
      game_id: 'game-1',
      total_score: 2,
      completed_at: '2026-04-20T13:00:00Z',
    }).as('completeGame');

    mountGamePage();
    cy.wait('@getLastGame');
    cy.get('[data-cy="btn-next"]').should('not.exist');
    cy.get('[data-cy="btn-view-results"]').should('exist');

    cy.get('[data-answer-id="c1"]').click();
    cy.get('[data-cy="btn-submit-answer"]').click();
    cy.wait('@submitLastAnswer');
    cy.get('[data-cy="btn-view-results"]').click();
    cy.wait('@completeGame');

    cy.get('[data-cy="results-page"]').should('exist');
  });

  it('redirects to results if game is already completed', () => {
    const completedGame: GameDetailsResponse = {
      ...mockGame,
      completed_at: '2026-04-20T13:00:00Z',
    };

    cy.intercept('GET', '**/api/games/game-1', completedGame).as('getCompletedGame');
    mountGamePage();
    cy.wait('@getCompletedGame');
    cy.get('[data-cy="results-page"]').should('exist');
  });

  it('shows error when game load fails', () => {
    cy.intercept('GET', '**/api/games/game-1', {
      statusCode: 500,
      body: { error: 'Failed to load game.' },
    }).as('getGameError');

    mountGamePage();
    cy.wait('@getGameError');
    cy.get('[data-cy="game-error"]').should('contain.text', 'Failed to load game.');
  });
});

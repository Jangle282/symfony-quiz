import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import ResultsPage from '../../src/pages/ResultsPage';

function mountResultsPage(route = '/results/game-1') {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: { retry: false },
      mutations: { retry: false },
    },
  });

  cy.mount(
    <QueryClientProvider client={queryClient}>
      <MemoryRouter initialEntries={[route]}>
        <Routes>
          <Route path="/results/:id" element={<ResultsPage />} />
          <Route path="/lobby" element={<div data-cy="lobby-page">Lobby</div>} />
        </Routes>
      </MemoryRouter>
    </QueryClientProvider>,
  );
}

describe('ResultsPage', () => {
  beforeEach(() => {
    window.localStorage.setItem('token', 'test-token');
  });

  it('shows loading while fetching results', () => {
    cy.intercept('GET', '**/api/games/game-1/results', () => {
      // Keep request pending to assert loading state.
    }).as('getResultsPending');

    mountResultsPage();
    cy.get('[data-cy="results-loading"]').should('exist');
  });

  it('renders score, percentage, and breakdown', () => {
    cy.intercept('GET', '**/api/games/game-1/results', {
      game_id: 'game-1',
      total_score: 2,
      total_questions: 5,
      questions: [
        {
          question_id: 'q1',
          question_text: 'Capital of France?',
          correct_answer: 'Paris',
          selected_answer: 'Paris',
          is_correct: true,
        },
        {
          question_id: 'q2',
          question_text: '2 + 2 = ?',
          correct_answer: '4',
          selected_answer: '5',
          is_correct: false,
        },
      ],
    }).as('getResults');

    mountResultsPage();
    cy.wait('@getResults');

    cy.get('[data-cy="results-score"]').should('contain.text', '2 / 5');
    cy.get('[data-cy="results-percentage"]').should('contain.text', '40%');
    cy.get('[data-cy="question-breakdown-item"]').should('have.length', 2);
    cy.get('[data-cy="question-breakdown-status"]').first().should('contain.text', 'Correct');
    cy.get('[data-cy="question-breakdown-status"]').eq(1).should('contain.text', 'Incorrect');
  });

  it('opens and closes delete modal', () => {
    cy.intercept('GET', '**/api/games/game-1/results', {
      game_id: 'game-1',
      total_score: 1,
      total_questions: 1,
      questions: [],
    }).as('getResults');

    mountResultsPage();
    cy.wait('@getResults');

    cy.get('[data-cy="btn-show-delete-modal"]').click();
    cy.get('[data-cy="results-delete-modal-title"]').should('contain.text', 'Delete this game?');
    cy.get('[data-cy="results-delete-modal-cancel"]').click();
    cy.get('[data-cy="results-delete-modal-title"]').should('not.exist');
  });

  it('deletes game and navigates to lobby', () => {
    cy.intercept('GET', '**/api/games/game-1/results', {
      game_id: 'game-1',
      total_score: 1,
      total_questions: 1,
      questions: [],
    }).as('getResults');

    cy.intercept('DELETE', '**/api/games/game-1', { statusCode: 204 }).as('deleteGame');

    mountResultsPage();
    cy.wait('@getResults');

    cy.get('[data-cy="btn-show-delete-modal"]').click();
    cy.get('[data-cy="results-delete-modal-confirm"]').click();
    cy.wait('@deleteGame');

    cy.get('[data-cy="lobby-page"]').should('exist');
  });

  it('shows fetch error state', () => {
    cy.intercept('GET', '**/api/games/game-1/results', {
      statusCode: 500,
      body: { error: 'Results unavailable.' },
    }).as('getResultsError');

    mountResultsPage();
    cy.wait('@getResultsError');

    cy.get('[data-cy="results-error"]').should('contain.text', 'Results unavailable.');
  });

  it('shows delete error when deletion fails', () => {
    cy.intercept('GET', '**/api/games/game-1/results', {
      game_id: 'game-1',
      total_score: 1,
      total_questions: 1,
      questions: [],
    }).as('getResults');

    cy.intercept('DELETE', '**/api/games/game-1', {
      statusCode: 403,
      body: { error: 'Access denied.' },
    }).as('deleteGameError');

    mountResultsPage();
    cy.wait('@getResults');

    cy.get('[data-cy="btn-show-delete-modal"]').click();
    cy.get('[data-cy="results-delete-modal-confirm"]').click();
    cy.wait('@deleteGameError');

    cy.get('[data-cy="results-delete-error"]').should('contain.text', 'Access denied.');
  });
});

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { AuthContext, type AuthContextValue } from '../../src/context/AuthContext';
import ProfilePage from '../../src/pages/ProfilePage';
import type { User } from '../../src/types';

const baseUser: User = {
  id: 'user-1',
  username: 'alice',
  createdAt: '2026-04-20T12:00:00Z',
  updatedAt: '2026-04-20T12:00:00Z',
};

function mountProfilePage(authOverrides: Partial<AuthContextValue> = {}) {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: { retry: false },
      mutations: { retry: false },
    },
  });

  const authValue: AuthContextValue = {
    user: baseUser,
    token: 'test-token',
    isAuthenticated: true,
    isLoading: false,
    login: async () => {},
    register: async () => {},
    logout: async () => {},
    setUser: () => {},
    ...authOverrides,
  };

  cy.mount(
    <QueryClientProvider client={queryClient}>
      <AuthContext.Provider value={authValue}>
        <MemoryRouter initialEntries={['/user']}>
          <Routes>
            <Route path="/user" element={<ProfilePage />} />
            <Route path="/lobby" element={<div data-cy="lobby-page">Lobby</div>} />
          </Routes>
        </MemoryRouter>
      </AuthContext.Provider>
    </QueryClientProvider>,
  );
}

function mockProfileResponse() {
  return {
    user: baseUser,
    games: [
      {
        id: 'game-completed',
        role: 'host',
        joinedAt: '2026-04-20T12:00:00Z',
        createdBy: 'user-1',
        totalScore: 7,
        startedAt: '2026-04-20T12:00:00Z',
        completedAt: '2026-04-20T12:30:00Z',
      },
      {
        id: 'game-active',
        role: 'participant',
        joinedAt: '2026-04-22T12:00:00Z',
        createdBy: 'user-2',
        totalScore: 0,
        startedAt: '2026-04-22T12:00:00Z',
        completedAt: null,
      },
    ],
  };
}

describe('ProfilePage', () => {
  beforeEach(() => {
    window.localStorage.setItem('token', 'test-token');
  });

  it('shows loading while fetching profile', () => {
    cy.intercept('GET', '**/api/user/user-1', () => {
      // Keep request pending to assert loading state.
    }).as('getProfilePending');

    mountProfilePage();
    cy.get('[data-cy="profile-loading"]').should('exist');
  });

  it('renders profile info and completed game history', () => {
    cy.intercept('GET', '**/api/user/user-1', mockProfileResponse()).as('getProfile');

    mountProfilePage();
    cy.wait('@getProfile');

    cy.get('[data-cy="profile-username"]').should('contain.text', 'alice');
    cy.get('[data-cy="game-history-item"]').should('have.length', 1);
    cy.get('[data-cy="game-history-score"]').should('contain.text', 'Score: 7');
  });

  it('validates username before submit', () => {
    cy.intercept('GET', '**/api/user/user-1', mockProfileResponse()).as('getProfile');

    mountProfilePage();
    cy.wait('@getProfile');

    cy.get('[data-cy="username-input"]').clear().type('alice');
    cy.get('[data-cy="username-submit"]').click();

    cy.get('[data-cy="username-validation-error"]').should(
      'contain.text',
      'Please choose a different username.',
    );
  });

  it('updates username successfully', () => {
    const updatedUser = { ...baseUser, username: 'alice-new' };

    cy.intercept('GET', '**/api/user/user-1', {
      user: baseUser,
      games: [],
    }).as('getProfile');

    cy.intercept('PATCH', '**/api/user/user-1/username', {
      user: updatedUser,
    }).as('patchUsername');

    mountProfilePage({ setUser: cy.stub().as('setUser') });
    cy.wait('@getProfile');

    cy.get('[data-cy="username-input"]').clear().type('alice-new');
    cy.get('[data-cy="username-submit"]').click();

    cy.wait('@patchUsername');
    cy.get('@setUser').should('have.been.calledOnce');
    cy.get('[data-cy="username-success"]').should('contain.text', 'Username updated successfully.');
  });

  it('validates password before submit', () => {
    cy.intercept('GET', '**/api/user/user-1', mockProfileResponse()).as('getProfile');

    mountProfilePage();
    cy.wait('@getProfile');

    cy.get('[data-cy="current-password-input"]').type('current-pass');
    cy.get('[data-cy="new-password-input"]').type('short');
    cy.get('[data-cy="password-submit"]').click();

    cy.get('[data-cy="password-validation-error"]').should(
      'contain.text',
      'New password must be at least 10 characters long.',
    );
  });

  it('updates password successfully', () => {
    cy.intercept('GET', '**/api/user/user-1', mockProfileResponse()).as('getProfile');

    cy.intercept('PATCH', '**/api/user/user-1/password', {
      message: 'Password updated successfully.',
    }).as('patchPassword');

    mountProfilePage();
    cy.wait('@getProfile');

    cy.get('[data-cy="current-password-input"]').type('current-pass-123');
    cy.get('[data-cy="new-password-input"]').type('new-pass-12345');
    cy.get('[data-cy="password-submit"]').click();

    cy.wait('@patchPassword');
    cy.get('[data-cy="password-success"]').should('contain.text', 'Password updated successfully.');
  });

  it('deletes a game from history', () => {
    cy.intercept('GET', '**/api/user/user-1', mockProfileResponse()).as('getProfile');

    cy.intercept('DELETE', '**/api/games/game-completed', { statusCode: 204 }).as('deleteGame');

    mountProfilePage();
    cy.wait('@getProfile');

    cy.get('[data-cy="game-history-delete-game-completed"]').click();
    cy.get('[data-cy="profile-delete-modal-confirm"]').click();

    cy.wait('@deleteGame');
  });

  it('shows API error when username update fails', () => {
    cy.intercept('GET', '**/api/user/user-1', mockProfileResponse()).as('getProfile');

    cy.intercept('PATCH', '**/api/user/user-1/username', {
      statusCode: 409,
      body: { error: 'Username already exists.' },
    }).as('patchUsernameError');

    mountProfilePage();
    cy.wait('@getProfile');

    cy.get('[data-cy="username-input"]').clear().type('taken-name');
    cy.get('[data-cy="username-submit"]').click();

    cy.wait('@patchUsernameError');
    cy.get('[data-cy="username-api-error"]').should('contain.text', 'Username already exists.');
  });
});

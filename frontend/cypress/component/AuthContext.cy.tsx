import { useContext } from 'react';
import { AuthContext, AuthProvider } from '../../src/context/AuthContext';
import { api } from '../../src/api/client';
import type { User } from '../../src/types';

// ---------------------------------------------------------------------------
// Consumer helper — renders current AuthContext state as data attributes so
// Cypress assertions can read them without inspecting React internals.
// ---------------------------------------------------------------------------

function AuthConsumer() {
  const ctx = useContext(AuthContext)!;
  return (
    <div>
      <span data-cy="loading">{String(ctx.isLoading)}</span>
      <span data-cy="authenticated">{String(ctx.isAuthenticated)}</span>
      <span data-cy="username">{ctx.user?.username ?? 'null'}</span>
      <span data-cy="token">{ctx.token ?? 'null'}</span>
      <button data-cy="login" onClick={() => ctx.login('alice', 'Password1!')}>
        login
      </button>
      <button data-cy="logout" onClick={() => void ctx.logout()}>
        logout
      </button>
      <button
        data-cy="set-user"
        onClick={() =>
          ctx.setUser({
            id: '1',
            username: 'updated',
            createdAt: '',
            updatedAt: '',
          })
        }
      >
        set-user
      </button>
    </div>
  );
}

function mount() {
  cy.mount(
    <AuthProvider>
      <AuthConsumer />
    </AuthProvider>,
  );
}

const baseUser: User = {
  id: '1',
  username: 'alice',
  createdAt: '2026-01-01T00:00:00Z',
  updatedAt: '2026-01-01T00:00:00Z',
};

const authPayload = {
  token: 'access-token',
  refresh_token: 'refresh-token',
  user: baseUser,
};

beforeEach(() => {
  cy.clearLocalStorage();
});

// ---------------------------------------------------------------------------
// Cold start scenarios
// ---------------------------------------------------------------------------

describe('cold start — no tokens', () => {
  it('is not authenticated and not loading', () => {
    mount();

    cy.get('[data-cy="loading"]').should('have.text', 'false');
    cy.get('[data-cy="authenticated"]').should('have.text', 'false');
    cy.get('[data-cy="username"]').should('have.text', 'null');
  });
});

describe('cold start — valid token + user in localStorage', () => {
  it('validates the token via GET /user/:id and sets the user', () => {
    window.localStorage.setItem('token', 'stored-token');
    window.localStorage.setItem('user', JSON.stringify(baseUser));

    cy.intercept('GET', '**/api/user/1', { user: baseUser }).as('getUser');

    mount();

    cy.wait('@getUser');
    cy.get('[data-cy="loading"]').should('have.text', 'false');
    cy.get('[data-cy="authenticated"]').should('have.text', 'true');
    cy.get('[data-cy="username"]').should('have.text', 'alice');
  });

  it('clears state and becomes unauthenticated when GET /user/:id fails', () => {
    window.localStorage.setItem('token', 'bad-token');
    window.localStorage.setItem('user', JSON.stringify(baseUser));

    cy.intercept('GET', '**/api/user/1', { statusCode: 401 }).as('getUser');

    mount();

    cy.wait('@getUser');
    cy.get('[data-cy="loading"]').should('have.text', 'false');
    cy.get('[data-cy="authenticated"]').should('have.text', 'false');
    cy.get('[data-cy="username"]').should('have.text', 'null');
  });
});

describe('cold start — only refresh_token stored', () => {
  it('calls POST /token/refresh and becomes authenticated on success', () => {
    window.localStorage.setItem('refresh_token', 'stored-refresh');

    cy.intercept('POST', '**/api/token/refresh', authPayload).as('refresh');

    mount();

    cy.wait('@refresh');
    cy.get('[data-cy="loading"]').should('have.text', 'false');
    cy.get('[data-cy="authenticated"]').should('have.text', 'true');
    cy.get('[data-cy="username"]').should('have.text', 'alice');
  });

  it('stays unauthenticated when POST /token/refresh fails', () => {
    window.localStorage.setItem('refresh_token', 'bad-refresh');

    cy.intercept('POST', '**/api/token/refresh', { statusCode: 401 }).as('refresh');

    mount();

    cy.wait('@refresh');
    cy.get('[data-cy="loading"]').should('have.text', 'false');
    cy.get('[data-cy="authenticated"]').should('have.text', 'false');
  });
});

// ---------------------------------------------------------------------------
// login()
// ---------------------------------------------------------------------------

describe('login()', () => {
  it('stores credentials in localStorage and sets authenticated state', () => {
    cy.intercept('POST', '**/api/login', authPayload).as('login');

    mount();
    cy.get('[data-cy="login"]').click();

    cy.wait('@login');
    cy.get('[data-cy="authenticated"]').should('have.text', 'true');
    cy.get('[data-cy="username"]').should('have.text', 'alice');
    cy.get('[data-cy="token"]').should('have.text', 'access-token');

    cy.window().then((win) => {
      expect(win.localStorage.getItem('token')).to.eq('access-token');
      expect(win.localStorage.getItem('refresh_token')).to.eq('refresh-token');
    });
  });

  it('propagates the error and leaves state unchanged when login fails', () => {
    cy.intercept('POST', '**/api/login', { statusCode: 401 }).as('login');

    // Override login button to catch the rejection so Cypress does not fail
    cy.mount(
      <AuthProvider>
        <div>
          {(() => {
            function FailConsumer() {
              const ctx = useContext(AuthContext)!;
              return (
                <>
                  <span data-cy="authenticated">{String(ctx.isAuthenticated)}</span>
                  <button
                    data-cy="login"
                    onClick={() => ctx.login('alice', 'bad').catch(() => {})}
                  >
                    login
                  </button>
                </>
              );
            }
            return <FailConsumer />;
          })()}
        </div>
      </AuthProvider>,
    );

    cy.get('[data-cy="login"]').click();
    cy.wait('@login');
    cy.get('[data-cy="authenticated"]').should('have.text', 'false');
  });
});

// ---------------------------------------------------------------------------
// logout()
// ---------------------------------------------------------------------------

describe('logout()', () => {
  it('clears localStorage and sets unauthenticated state on successful logout', () => {
    window.localStorage.setItem('token', 'stored-token');
    window.localStorage.setItem('user', JSON.stringify(baseUser));

    cy.intercept('GET', '**/api/user/1', { user: baseUser }).as('getUser');
    cy.intercept('POST', '**/api/logout', { statusCode: 200 }).as('logout');

    mount();

    cy.wait('@getUser');
    cy.get('[data-cy="authenticated"]').should('have.text', 'true');

    cy.get('[data-cy="logout"]').click();
    cy.wait('@logout');

    cy.get('[data-cy="authenticated"]').should('have.text', 'false');
    cy.window().then((win) => {
      expect(win.localStorage.getItem('token')).to.be.null;
      expect(win.localStorage.getItem('refresh_token')).to.be.null;
    });
  });

  it('still clears local state even when the logout API call fails', () => {
    window.localStorage.setItem('token', 'stored-token');
    window.localStorage.setItem('user', JSON.stringify(baseUser));

    cy.intercept('GET', '**/api/user/1', { user: baseUser }).as('getUser');
    cy.intercept('POST', '**/api/logout', { statusCode: 500 }).as('logout');

    mount();

    cy.wait('@getUser');
    cy.get('[data-cy="logout"]').click();
    cy.wait('@logout');

    cy.get('[data-cy="authenticated"]').should('have.text', 'false');
    cy.window().then((win) => {
      expect(win.localStorage.getItem('token')).to.be.null;
    });
  });
});

// ---------------------------------------------------------------------------
// Auth failure callback (triggered by API client on unrecoverable 401)
// ---------------------------------------------------------------------------

// A variant consumer that also exposes a button to make a protected API call
function AuthConsumerWithTrigger() {
  const ctx = useContext(AuthContext)!;
  return (
    <div>
      <span data-cy="loading">{String(ctx.isLoading)}</span>
      <span data-cy="authenticated">{String(ctx.isAuthenticated)}</span>
      <span data-cy="username">{ctx.user?.username ?? 'null'}</span>
      <span data-cy="token">{ctx.token ?? 'null'}</span>
      <button
        data-cy="trigger-protected"
        onClick={() => api.get('/games/any').catch(() => { /* expected 401 */ })}
      >
        fetch
      </button>
    </div>
  );
}

describe('auth failure callback', () => {
  it('clears token and user when the API client signals an auth failure', () => {
    window.localStorage.setItem('token', 'stored-token');
    window.localStorage.setItem('user', JSON.stringify(baseUser));
    // No refresh_token — so when a mid-session 401 occurs the response interceptor
    // calls performTokenRefresh, finds no refresh_token, invokes handleAuthFailure
    // → authFailureCallback → clears React state.

    cy.intercept('GET', '**/api/user/1', { user: baseUser }).as('getUser');
    cy.intercept('GET', /\/games\/any/, { statusCode: 401 }).as('protectedCall');

    cy.mount(
      <AuthProvider>
        <AuthConsumerWithTrigger />
      </AuthProvider>,
    );

    cy.wait('@getUser');
    cy.get('[data-cy="authenticated"]').should('have.text', 'true');

    cy.get('[data-cy="trigger-protected"]').click();
    cy.wait('@protectedCall');

    cy.get('[data-cy="authenticated"]').should('have.text', 'false');
    cy.get('[data-cy="username"]').should('have.text', 'null');
  });
});

// ---------------------------------------------------------------------------
// Cross-tab logout via storage event
// ---------------------------------------------------------------------------

describe('cross-tab logout', () => {
  it('clears auth state when another tab removes the token from localStorage', () => {
    window.localStorage.setItem('token', 'stored-token');
    window.localStorage.setItem('user', JSON.stringify(baseUser));

    cy.intercept('GET', '**/api/user/1', { user: baseUser }).as('getUser');

    mount();
    cy.wait('@getUser');
    cy.get('[data-cy="authenticated"]').should('have.text', 'true');

    // Simulate another tab removing the token
    cy.window().then((win) => {
      win.dispatchEvent(
        new StorageEvent('storage', { key: 'token', newValue: null }),
      );
    });

    cy.get('[data-cy="authenticated"]').should('have.text', 'false');
    cy.get('[data-cy="username"]').should('have.text', 'null');
  });
});

// ---------------------------------------------------------------------------
// setUser()
// ---------------------------------------------------------------------------

describe('setUser()', () => {
  it('updates the user in React state and persists to localStorage', () => {
    window.localStorage.setItem('token', 'stored-token');
    window.localStorage.setItem('user', JSON.stringify(baseUser));

    cy.intercept('GET', '**/api/user/1', { user: baseUser }).as('getUser');

    mount();
    cy.wait('@getUser');

    cy.get('[data-cy="set-user"]').click();

    cy.get('[data-cy="username"]').should('have.text', 'updated');
    cy.window().then((win) => {
      const stored = JSON.parse(win.localStorage.getItem('user')!);
      expect(stored.username).to.eq('updated');
    });
  });
});

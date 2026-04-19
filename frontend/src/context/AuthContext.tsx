import {
  createContext,
  useCallback,
  useEffect,
  useState,
  type ReactNode,
} from 'react';
import type { User } from '../types';
import * as authService from '../api/authService';
import * as userService from '../api/userService';
import { setAuthFailureCallback, performTokenRefresh } from '../api/client';

export interface AuthContextValue {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (username: string, password: string) => Promise<void>;
  register: (username: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  setUser: (user: User) => void;
}

// eslint-disable-next-line react-refresh/only-export-components
export const AuthContext = createContext<AuthContextValue | null>(null);

function loadStoredUser(): User | null {
  try {
    const raw = localStorage.getItem('user');
    return raw ? (JSON.parse(raw) as User) : null;
  } catch {
    return null;
  }
}

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(loadStoredUser);
  const [token, setToken] = useState<string | null>(
    () => localStorage.getItem('token'),
  );
  const [isLoading, setIsLoading] = useState(
    () => !!(localStorage.getItem('token') || localStorage.getItem('refresh_token')),
  );

  const isAuthenticated = !!token && !!user;

  // Let the API client signal auth failures back to React state
  useEffect(() => {
    setAuthFailureCallback(() => {
      setToken(null);
      setUser(null);
    });
    return () => setAuthFailureCallback(null);
  }, []);

  // Sync auth state when another tab clears the token
  useEffect(() => {
    function handleStorageChange(e: StorageEvent) {
      if (e.key === 'token' && e.newValue === null) {
        setToken(null);
        setUser(null);
      }
    }
    window.addEventListener('storage', handleStorageChange);
    return () => window.removeEventListener('storage', handleStorageChange);
  }, []);

  useEffect(() => {
    let cancelled = false;
    const storedToken = localStorage.getItem('token');
    const refreshTokenValue = localStorage.getItem('refresh_token');
    const storedUser = loadStoredUser();

    if (storedToken && storedUser) {
      userService
        .getUser(storedUser.id)
        .then((data) => {
          if (cancelled) return;
          localStorage.setItem('user', JSON.stringify(data.user));
          setUser(data.user);
        })
        .catch(() => {
          if (cancelled) return;
          localStorage.removeItem('user');
          setToken(null);
          setUser(null);
        })
        .finally(() => {
          if (!cancelled) setIsLoading(false);
        });
    } else if (refreshTokenValue) {
      performTokenRefresh()
        .then((data) => {
          if (cancelled) return;
          localStorage.setItem('user', JSON.stringify(data.user));
          setToken(data.token);
          setUser(data.user);
        })
        .catch(() => {
          if (cancelled) return;
          localStorage.removeItem('user');
          setToken(null);
          setUser(null);
        })
        .finally(() => {
          if (!cancelled) setIsLoading(false);
        });
    } else {
      setIsLoading(false);
    }

    return () => {
      cancelled = true;
    };
    // Run only on mount
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const login = useCallback(async (username: string, password: string) => {
    const data = await authService.login(username, password);
    localStorage.setItem('token', data.token);
    localStorage.setItem('refresh_token', data.refresh_token);
    localStorage.setItem('user', JSON.stringify(data.user));
    setToken(data.token);
    setUser(data.user);
  }, []);

  const register = useCallback(async (username: string, password: string) => {
    await authService.register(username, password);
  }, []);

  const logout = useCallback(async () => {
    try {
      await authService.logout();
    } catch {
      // Ignore errors — clear local state regardless
    }
    localStorage.removeItem('token');
    localStorage.removeItem('refresh_token');
    localStorage.removeItem('user');
    setToken(null);
    setUser(null);
  }, []);

  const updateUser = useCallback((u: User) => {
    localStorage.setItem('user', JSON.stringify(u));
    setUser(u);
  }, []);

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        isAuthenticated,
        isLoading,
        login,
        register,
        logout,
        setUser: updateUser,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

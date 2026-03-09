import { createContext, useCallback, useEffect, useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import type { User, AuthStatus } from '../types/auth';
import AuthService from '../services/authService';

export interface AuthContextType {
  user: User | null;
  status: AuthStatus;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
  isAuthenticated: boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

interface AuthProviderProps {
  children: ReactNode;
}

export function AuthProvider({ children }: AuthProviderProps) {
  const [user, setUser] = useState<User | null>(null);
  const [status, setStatus] = useState<AuthStatus>('loading');

  useEffect(() => {
    const initAuth = async () => {
      try {
        if (AuthService.isAuthenticated()) {
          const userData = AuthService.getStoredUser();
          if (userData) {
            setUser(userData);
            setStatus('authenticated');

            try {
              const currentUser = await AuthService.getCurrentUser();
              setUser(currentUser);
            } catch {
              // Token is invalid, clear auth
              AuthService.clearAuth();
              setUser(null);
              setStatus('unauthenticated');
            }
          } else {
            setStatus('unauthenticated');
          }
        } else {
          setStatus('unauthenticated');
        }
      } catch (error) {
        console.error('Auth initialization error:', error);
        setStatus('unauthenticated');
      }
    };

    initAuth();
  }, []);

  const login = useCallback(async (email: string, password: string) => {
    try {
      setStatus('loading');
      const response = await AuthService.login({ email, password });
      setUser(response.user);
      setStatus('authenticated');
    } catch (error) {
      setStatus('unauthenticated');
      throw error;
    }
  }, []);

  const logout = useCallback(async () => {
    try {
      await AuthService.logout();
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      setUser(null);
      setStatus('unauthenticated');
    }
  }, []);

  const refreshUser = useCallback(async () => {
    try {
      const currentUser = await AuthService.getCurrentUser();
      setUser(currentUser);
    } catch (error) {
      console.error('Refresh user error:', error);
      await logout();
    }
  }, [logout]);

  // Memoizar o valor do contexto para evitar re-renders desnecessários
  const value: AuthContextType = useMemo(() => ({
    user,
    status,
    login,
    logout,
    refreshUser,
    isAuthenticated: status === 'authenticated',
  }), [user, status, login, logout, refreshUser]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export { AuthContext };
export default AuthContext;

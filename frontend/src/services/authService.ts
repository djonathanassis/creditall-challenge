import type {
  User,
  LoginRequest,
  LoginResponse,
  AuthStatus,
} from '../types/auth';
import { apiPost, apiGet, TokenManager, handleApiError } from './api';
import { API_ENDPOINTS, STORAGE_KEYS } from '../utils/constants';

export class AuthService {
  static async login(credentials: LoginRequest): Promise<LoginResponse> {
    try {
      const response = await apiPost<LoginResponse>(
        API_ENDPOINTS.AUTH.LOGIN,
        credentials
      );

      TokenManager.set(response.token);
      localStorage.setItem(
        STORAGE_KEYS.USER_DATA,
        JSON.stringify(response.user)
      );

      return response;
    } catch (error) {
      const apiError = handleApiError(error);
      throw new Error(apiError.message);
    }
  }

  static async logout(): Promise<void> {
    try {
      await apiPost(API_ENDPOINTS.AUTH.LOGOUT);
    } catch (error) {
      console.warn('Logout request failed, but clearing local data:', error);
    } finally {
      TokenManager.clear();
    }
  }

  static async getCurrentUser(): Promise<User> {
    try {
      const user = await apiGet<User>(API_ENDPOINTS.AUTH.ME);
      localStorage.setItem(STORAGE_KEYS.USER_DATA, JSON.stringify(user));

      return user;
    } catch (error) {
      const apiError = handleApiError(error);
      throw new Error(apiError.message);
    }
  }

  static getStoredUser(): User | null {
    try {
      const userData = localStorage.getItem(STORAGE_KEYS.USER_DATA);
      return userData ? JSON.parse(userData) : null;
    } catch (error) {
      console.error('Error parsing stored user data:', error);
      return null;
    }
  }

  static isAuthenticated(): boolean {
    return TokenManager.isValid() && this.getStoredUser() !== null;
  }

  static getAuthStatus(): AuthStatus {
    if (this.isAuthenticated()) {
      return 'authenticated';
    }
    return 'unauthenticated';
  }

  static getToken(): string | null {
    return TokenManager.get();
  }

  static setToken(token: string): void {
    TokenManager.set(token);
  }

  static clearAuth(): void {
    TokenManager.clear();
  }

  static async refreshUser(): Promise<User> {
    if (!this.isAuthenticated()) {
      throw new Error('Usuário não está autenticado');
    }

    return this.getCurrentUser();
  }

  static async validateToken(): Promise<boolean> {
    try {
      await this.getCurrentUser();
      return true;
    } catch {
      this.clearAuth();
      return false;
    }
  }
}

export const {
  login,
  logout,
  getCurrentUser,
  getStoredUser,
  isAuthenticated,
  getAuthStatus,
  getToken,
  setToken,
  clearAuth,
  refreshUser,
  validateToken,
} = AuthService;

export default AuthService;

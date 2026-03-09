export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
  is_deleted: boolean;
  deleted_at: string | null;
}

export interface LoginRequest {
  email: string;
  password: string;
}

export interface LoginResponse {
  user: User;
  token: string;
}

export interface AuthToken {
  token: string;
  type: 'Bearer';
  expires_at?: string;
}

export interface AuthError {
  message: string;
  code?: string;
}

export type AuthStatus = 'authenticated' | 'unauthenticated' | 'loading';

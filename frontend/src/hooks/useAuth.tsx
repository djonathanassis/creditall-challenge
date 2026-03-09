import { useContext } from 'react';
import AuthContext, { type AuthContextType } from '../context/AuthContext.tsx';

export function useAuth(): AuthContextType {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}

export default useAuth;

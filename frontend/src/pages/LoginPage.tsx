import { useState } from 'react';
import type { FormEvent } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth.tsx';
import { LoadingSpinner } from '../components/Loading';
import { notificationManager } from '../utils/notificationManager';

interface LocationState {
  from: {
    pathname: string;
  };
}

export default function LoginPage() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  const { login, isAuthenticated } = useAuth();
  const location = useLocation();

  const from =
    (location.state as LocationState)?.from?.pathname || '/dashboard';

  if (isAuthenticated) {
    return <Navigate to={from} replace />;
  }

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();

    if (!email.trim() || !password.trim()) {
      notificationManager.error(
        'Erro de validação',
        'Email e senha são obrigatórios'
      );
      return;
    }

    setIsLoading(true);

    try {
      await login(email.trim(), password);
      notificationManager.success('Login realizado', 'Bem-vindo ao sistema!');
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Erro no login';
      notificationManager.error('Erro no login', message);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className='min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8'>
      <div className='sm:mx-auto sm:w-full sm:max-w-md'>
        <div className='text-center'>
          <h1 className='text-3xl font-bold text-gray-900'>💼</h1>
          <h2 className='mt-2 text-2xl font-bold text-gray-900'>
            Sistema de Gestão de Vendas
          </h2>
          <p className='mt-2 text-sm text-gray-600'>
            Faça login para continuar
          </p>
        </div>
      </div>

      <div className='mt-8 sm:mx-auto sm:w-full sm:max-w-md'>
        <div className='card'>
          <form className='space-y-6' onSubmit={handleSubmit}>
            <div>
              <label htmlFor='email' className='form-label'>
                Email
              </label>
              <div className='mt-1'>
                <input
                  id='email'
                  name='email'
                  type='email'
                  autoComplete='email'
                  required
                  className='form-input'
                  placeholder='seu@email.com'
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  disabled={isLoading}
                />
              </div>
            </div>

            <div>
              <label htmlFor='password' className='form-label'>
                Senha
              </label>
              <div className='mt-1'>
                <input
                  id='password'
                  name='password'
                  type='password'
                  autoComplete='current-password'
                  required
                  className='form-input'
                  placeholder='Sua senha'
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  disabled={isLoading}
                />
              </div>
            </div>

            <div>
              <button
                type='submit'
                disabled={isLoading}
                className='btn-primary w-full flex justify-center py-3'
              >
                {isLoading ? (
                  <>
                    <LoadingSpinner size='sm' />
                    <span className='ml-2'>Fazendo login...</span>
                  </>
                ) : (
                  'Entrar'
                )}
              </button>
            </div>
          </form>

          <div className='mt-6'>
            <div className='relative'>
              <div className='absolute inset-0 flex items-center'>
                <div className='w-full border-t border-gray-300' />
              </div>
              <div className='relative flex justify-center text-sm'>
                <span className='px-2 bg-white text-gray-500'>
                  Sistema desenvolvido para gestão de vendas
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

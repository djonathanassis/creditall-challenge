import { memo, useCallback, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth.tsx';

const navigation = [
  { name: 'Dashboard', href: '/dashboard', icon: '📊' },
  { name: 'Produtos', href: '/products', icon: '📦' },
  { name: 'Clientes', href: '/customers', icon: '👥' },
  { name: 'Vendas', href: '/sales', icon: '💰' },
];

function Navigation() {
  const [isOpen, setIsOpen] = useState(false);
  const location = useLocation();
  const navigate = useNavigate();
  const { user, logout } = useAuth();

  const handleLogout = useCallback(async () => {
    try {
      await logout();
      navigate('/login');
    } catch (error) {
      console.error('Logout error:', error);
    }
  }, [logout, navigate]);

  const toggleMenu = useCallback(() => {
    setIsOpen(prev => !prev);
  }, []);

  return (
    <nav className='bg-white shadow-lg'>
      <div className='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8'>
        <div className='flex justify-between h-16'>
          <div className='flex'>
            {/* Logo */}
            <div className='flex-shrink-0 flex items-center'>
              <Link
                to='/dashboard'
                className='text-xl font-bold text-primary-600'
              >
                💼 Sales Manager
              </Link>
            </div>

            {/* Desktop Navigation */}
            <div className='hidden md:ml-6 md:flex md:space-x-8'>
              {navigation.map((item) => {
                const isActive = location.pathname.startsWith(item.href);
                return (
                  <Link
                    key={item.name}
                    to={item.href}
                    className={`${
                      isActive
                        ? 'border-primary-500 text-gray-900'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                    } inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors`}
                  >
                    <span className='mr-2'>{item.icon}</span>
                    {item.name}
                  </Link>
                );
              })}
            </div>
          </div>

          {/* User menu */}
          <div className='hidden md:ml-6 md:flex md:items-center'>
            <div className='flex items-center space-x-4'>
              <span className='text-sm text-gray-700'>
                Olá, <span className='font-medium'>{user?.name}</span>
              </span>
              <button onClick={handleLogout} className='btn-secondary text-sm'>
                Sair
              </button>
            </div>
          </div>

          {/* Mobile menu button */}
          <div className='md:hidden flex items-center'>
            <button
              onClick={toggleMenu}
              className='inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500'
            >
              <span className='sr-only'>Abrir menu principal</span>
              {isOpen ? (
                <svg
                  className='block h-6 w-6'
                  fill='none'
                  viewBox='0 0 24 24'
                  stroke='currentColor'
                >
                  <path
                    strokeLinecap='round'
                    strokeLinejoin='round'
                    strokeWidth={2}
                    d='M6 18L18 6M6 6l12 12'
                  />
                </svg>
              ) : (
                <svg
                  className='block h-6 w-6'
                  fill='none'
                  viewBox='0 0 24 24'
                  stroke='currentColor'
                >
                  <path
                    strokeLinecap='round'
                    strokeLinejoin='round'
                    strokeWidth={2}
                    d='M4 6h16M4 12h16M4 18h16'
                  />
                </svg>
              )}
            </button>
          </div>
        </div>
      </div>

      {/* Mobile menu */}
      {isOpen && (
        <div className='md:hidden'>
          <div className='pt-2 pb-3 space-y-1 sm:px-3'>
            {navigation.map((item) => {
              const isActive = location.pathname.startsWith(item.href);
              return (
                <Link
                  key={item.name}
                  to={item.href}
                  className={`${
                    isActive
                      ? 'bg-primary-50 border-primary-500 text-primary-700'
                      : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700'
                  } block pl-3 pr-4 py-2 border-l-4 text-base font-medium`}
                  onClick={() => setIsOpen(false)}
                >
                  <span className='mr-2'>{item.icon}</span>
                  {item.name}
                </Link>
              );
            })}
          </div>
          <div className='pt-4 pb-3 border-t border-gray-200'>
            <div className='flex items-center px-5'>
              <div className='flex-shrink-0'>
                <div className='h-10 w-10 rounded-full bg-primary-500 flex items-center justify-center text-white font-semibold'>
                  {user?.name.charAt(0).toUpperCase()}
                </div>
              </div>
              <div className='ml-3'>
                <div className='text-base font-medium text-gray-800'>
                  {user?.name}
                </div>
                <div className='text-sm font-medium text-gray-500'>
                  {user?.email}
                </div>
              </div>
            </div>
            <div className='mt-3 space-y-1'>
              <button
                onClick={handleLogout}
                className='block px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100 w-full text-left'
              >
                Sair
              </button>
            </div>
          </div>
        </div>
      )}
    </nav>
  );
}

// Export com memo para evitar re-renders desnecessários
export default memo(Navigation);

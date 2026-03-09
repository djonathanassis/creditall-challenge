/**
 * Main App component with routing and authentication
 */
import { lazy, Suspense } from 'react';
import {
  BrowserRouter as Router,
  Routes,
  Route,
  Navigate,
} from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import ProtectedRoute from './components/ProtectedRoute';
import Layout from './components/Layout';
import Loading from './components/Loading';
import './index.css';

// Lazy loading das páginas para melhor performance
const LoginPage = lazy(() => import('./pages/LoginPage'));
const DashboardPage = lazy(() => import('./pages/DashboardPage'));
const ProductsPage = lazy(() => import('./pages/ProductsPage'));
const ProductFormPage = lazy(() => import('./pages/ProductFormPage'));
const CustomersPage = lazy(() => import('./pages/CustomersPage'));
const CustomerFormPage = lazy(() => import('./pages/CustomerFormPage'));
const SalesPage = lazy(() => import('./pages/SalesPage'));
const SalesFormPage = lazy(() => import('./pages/SalesFormPage'));

// Componente wrapper para Suspense com loading
const SuspenseWrapper: React.FC<{ children: React.ReactNode }> = ({ 
  children 
}) => (
  <Suspense fallback={<Loading />}>
    {children}
  </Suspense>
);

function App() {
  return (
    <AuthProvider>
      <Router>
        <Routes>
          {/* Public Routes */}
          <Route path='/login' element={
            <SuspenseWrapper>
              <LoginPage />
            </SuspenseWrapper>
          } />

          {/* Protected Routes */}
          <Route
            path='/'
            element={
              <ProtectedRoute>
                <Layout>
                  <Navigate to='/dashboard' replace />
                </Layout>
              </ProtectedRoute>
            }
          />

          <Route
            path='/dashboard'
            element={
              <ProtectedRoute>
                <Layout>
                  <SuspenseWrapper>
                    <DashboardPage />
                  </SuspenseWrapper>
                </Layout>
              </ProtectedRoute>
            }
          />

          {/* Products Routes */}
          <Route
            path='/products'
            element={
              <ProtectedRoute>
                <Layout>
                  <SuspenseWrapper>
                    <ProductsPage />
                  </SuspenseWrapper>
                </Layout>
              </ProtectedRoute>
            }
          />

          <Route
            path='/products/new'
            element={
              <ProtectedRoute>
                <Layout>
                  <SuspenseWrapper>
                    <ProductFormPage />
                  </SuspenseWrapper>
                </Layout>
              </ProtectedRoute>
            }
          />

          <Route
            path='/products/:id/edit'
            element={
              <ProtectedRoute>
                <Layout>
                  <SuspenseWrapper>
                    <ProductFormPage />
                  </SuspenseWrapper>
                </Layout>
              </ProtectedRoute>
            }
          />

          {/* Customers Routes */}
          <Route
            path='/customers'
            element={
              <ProtectedRoute>
                <Layout>
                  <SuspenseWrapper>
                    <CustomersPage />
                  </SuspenseWrapper>
                </Layout>
              </ProtectedRoute>
            }
          />

          <Route
            path='/customers/new'
            element={
              <ProtectedRoute>
                <Layout>
                  <SuspenseWrapper>
                    <CustomerFormPage />
                  </SuspenseWrapper>
                </Layout>
              </ProtectedRoute>
            }
          />

          <Route
            path='/customers/:id/edit'
            element={
              <ProtectedRoute>
                <Layout>
                  <SuspenseWrapper>
                    <CustomerFormPage />
                  </SuspenseWrapper>
                </Layout>
              </ProtectedRoute>
            }
          />

          {/* Sales Routes */}
          <Route
            path='/sales'
            element={
              <ProtectedRoute>
                <Layout>
                  <SuspenseWrapper>
                    <SalesPage />
                  </SuspenseWrapper>
                </Layout>
              </ProtectedRoute>
            }
          />

          <Route
            path='/sales/new'
            element={
              <ProtectedRoute>
                <Layout>
                  <SuspenseWrapper>
                    <SalesFormPage />
                  </SuspenseWrapper>
                </Layout>
              </ProtectedRoute>
            }
          />

          {/* Catch all route - redirect to dashboard */}
          <Route path='*' element={<Navigate to='/dashboard' replace />} />
        </Routes>
      </Router>
    </AuthProvider>
  );
}

export default App;

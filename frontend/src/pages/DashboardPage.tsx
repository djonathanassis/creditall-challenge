import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import SalesService from '../services/salesService';
import ProductService from '../services/productService';
import CustomerService from '../services/customerService';
import { LoadingSpinner } from '../components/Loading';
import { notificationManager } from '../utils/notificationManager';
import { formatCurrency } from '../utils/formatters';

interface DashboardStats {
  totalSales: number;
  pendingSales: number;
  completedSales: number;
  cancelledSales: number;
  totalRevenue: number;
  totalProducts: number;
  totalCustomers: number;
  lowStockProducts: number;
}

export default function DashboardPage() {
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    loadDashboardStats();
  }, []);

  const loadDashboardStats = async () => {
    try {
      setIsLoading(true);

      // Load statistics from multiple services
      const [salesStats, products, customers] = await Promise.all([
        SalesService.getStatistics(),
        ProductService.getAll({ per_page: 1 }),
        CustomerService.getAll({ per_page: 1 }),
      ]);

      // Get low stock products
      const lowStockProducts = await ProductService.getLowStockProducts(10);

      const dashboardStats: DashboardStats = {
        ...salesStats,
        totalProducts: products.meta.total,
        totalCustomers: customers.meta.total,
        lowStockProducts: lowStockProducts.length,
      };

      setStats(dashboardStats);
    } catch (error) {
      console.error('Error loading dashboard stats:', error);
      notificationManager.error(
        'Erro ao carregar estatísticas',
        'Não foi possível carregar os dados do dashboard'
      );
    } finally {
      setIsLoading(false);
    }
  };

  if (isLoading) {
    return (
      <div className='flex items-center justify-center h-64'>
        <LoadingSpinner size='lg' text='Carregando estatísticas...' />
      </div>
    );
  }

  if (!stats) {
    return (
      <div className='text-center py-12'>
        <p className='text-gray-500'>
          Não foi possível carregar as estatísticas.
        </p>
        <button onClick={loadDashboardStats} className='btn-primary mt-4'>
          Tentar novamente
        </button>
      </div>
    );
  }

  const quickActions = [
    {
      name: 'Nova Venda',
      description: 'Registrar uma nova venda',
      href: '/sales/new',
      icon: '💰',
      color: 'bg-green-500 hover:bg-green-600',
    },
    {
      name: 'Novo Produto',
      description: 'Cadastrar um novo produto',
      href: '/products/new',
      icon: '📦',
      color: 'bg-blue-500 hover:bg-blue-600',
    },
    {
      name: 'Novo Cliente',
      description: 'Cadastrar um novo cliente',
      href: '/customers/new',
      icon: '👥',
      color: 'bg-purple-500 hover:bg-purple-600',
    },
    {
      name: 'Ver Vendas',
      description: 'Listar todas as vendas',
      href: '/sales',
      icon: '📊',
      color: 'bg-orange-500 hover:bg-orange-600',
    },
  ];

  return (
    <div className='space-y-6'>
      {/* Header */}
      <div className='md:flex md:items-center md:justify-between'>
        <div className='flex-1 min-w-0'>
          <h1 className='text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate'>
            Dashboard
          </h1>
          <p className='mt-1 text-sm text-gray-500'>
            Visão geral do seu negócio
          </p>
        </div>
        <div className='mt-4 flex md:mt-0 md:ml-4'>
          <button onClick={loadDashboardStats} className='btn-secondary'>
            🔄 Atualizar
          </button>
        </div>
      </div>

      {/* Statistics Cards */}
      <div className='grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4'>
        {/* Total Revenue */}
        <div className='card bg-gradient-to-r from-green-500 to-green-600 text-white'>
          <div className='flex items-center'>
            <div className='flex-shrink-0'>
              <div className='text-3xl'>💵</div>
            </div>
            <div className='ml-4 flex-1'>
              <p className='text-green-100 text-sm'>Receita Total</p>
              <p className='text-2xl font-semibold'>
                {formatCurrency(stats.totalRevenue)}
              </p>
            </div>
          </div>
        </div>

        {/* Total Sales */}
        <div className='card'>
          <div className='flex items-center'>
            <div className='flex-shrink-0'>
              <div className='text-3xl'>📊</div>
            </div>
            <div className='ml-4 flex-1'>
              <p className='text-gray-500 text-sm'>Total de Vendas</p>
              <p className='text-2xl font-semibold text-gray-900'>
                {stats.totalSales}
              </p>
            </div>
          </div>
        </div>

        {/* Total Products */}
        <div className='card'>
          <div className='flex items-center'>
            <div className='flex-shrink-0'>
              <div className='text-3xl'>📦</div>
            </div>
            <div className='ml-4 flex-1'>
              <p className='text-gray-500 text-sm'>Produtos</p>
              <p className='text-2xl font-semibold text-gray-900'>
                {stats.totalProducts}
              </p>
            </div>
          </div>
        </div>

        {/* Total Customers */}
        <div className='card'>
          <div className='flex items-center'>
            <div className='flex-shrink-0'>
              <div className='text-3xl'>👥</div>
            </div>
            <div className='ml-4 flex-1'>
              <p className='text-gray-500 text-sm'>Clientes</p>
              <p className='text-2xl font-semibold text-gray-900'>
                {stats.totalCustomers}
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Sales Status Overview */}
      <div className='grid grid-cols-1 gap-5 sm:grid-cols-3'>
        <div className='card'>
          <div className='text-center'>
            <div className='text-2xl mb-2'>⏳</div>
            <p className='text-sm text-gray-500'>Vendas Pendentes</p>
            <p className='text-xl font-semibold text-yellow-600'>
              {stats.pendingSales}
            </p>
          </div>
        </div>

        <div className='card'>
          <div className='text-center'>
            <div className='text-2xl mb-2'>✅</div>
            <p className='text-sm text-gray-500'>Vendas Pagas</p>
            <p className='text-xl font-semibold text-green-600'>
              {stats.completedSales}
            </p>
          </div>
        </div>

        <div className='card'>
          <div className='text-center'>
            <div className='text-2xl mb-2'>❌</div>
            <p className='text-sm text-gray-500'>Vendas Canceladas</p>
            <p className='text-xl font-semibold text-red-600'>
              {stats.cancelledSales}
            </p>
          </div>
        </div>
      </div>

      {/* Alerts */}
      {stats.lowStockProducts > 0 && (
        <div className='card bg-yellow-50 border-yellow-200'>
          <div className='flex'>
            <div className='flex-shrink-0'>
              <div className='text-xl'>⚠️</div>
            </div>
            <div className='ml-3'>
              <h3 className='text-sm font-medium text-yellow-800'>
                Atenção: Estoque Baixo
              </h3>
              <div className='mt-2 text-sm text-yellow-700'>
                <p>
                  {stats.lowStockProducts} produto
                  {stats.lowStockProducts > 1 ? 's' : ''} com estoque baixo
                  (menos de 10 unidades).
                </p>
                <Link
                  to='/products'
                  className='font-medium underline hover:text-yellow-600'
                >
                  Ver produtos com estoque baixo →
                </Link>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Quick Actions */}
      <div className='card'>
        <h3 className='text-lg font-medium text-gray-900 mb-4'>
          Ações Rápidas
        </h3>
        <div className='grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4'>
          {quickActions.map((action) => (
            <Link
              key={action.name}
              to={action.href}
              className={`${action.color} text-white rounded-lg p-4 hover:shadow-lg transition-all duration-200 transform hover:scale-105`}
            >
              <div className='text-center'>
                <div className='text-2xl mb-2'>{action.icon}</div>
                <h4 className='font-medium'>{action.name}</h4>
                <p className='text-sm opacity-90 mt-1'>{action.description}</p>
              </div>
            </Link>
          ))}
        </div>
      </div>
    </div>
  );
}

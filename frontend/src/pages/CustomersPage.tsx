import { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import type { Customer, CustomerFilters } from '../types/customer';
import CustomerService from '../services/customerService';
import { LoadingSpinner } from '../components/Loading';
import { notificationManager } from '../utils/notificationManager';
import { formatDate } from '../utils/formatters';
import type { PaginatedResponse } from '../types/api';

export default function CustomersPage() {
  const [customers, setCustomers] = useState<Customer[]>([]);
  const [pagination, setPagination] = useState<
    PaginatedResponse<Customer>['meta'] | null
  >(null);
  const [isLoading, setIsLoading] = useState(true);
  const [filters, setFilters] = useState<CustomerFilters>({
    page: 1,
    per_page: 15,
    search: '',
    include_deleted: false,
  });

  const loadCustomers = useCallback(async () => {
    try {
      setIsLoading(true);
      const response = await CustomerService.getAll(filters);

      if (response && Array.isArray(response.data)) {
        setCustomers(response.data);
        setPagination(response.meta);
      } else {
        console.warn('Invalid response format:', response);
        setCustomers([]);
        setPagination(null);
      }
    } catch (error) {
      console.error('Error loading customers:', error);
      setCustomers([]);
      setPagination(null);
      notificationManager.error(
        'Erro ao carregar clientes',
        'Não foi possível carregar a lista de clientes'
      );
    } finally {
      setIsLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    loadCustomers();
  }, [loadCustomers]);

  const handleSearch = (search: string) => {
    setFilters((prev) => ({ ...prev, search, page: 1 }));
  };

  const handlePageChange = (page: number) => {
    setFilters((prev) => ({ ...prev, page }));
  };

  const handleDeleteCustomer = async (id: number) => {
    if (!window.confirm('Tem certeza que deseja excluir este cliente?')) {
      return;
    }

    try {
      await CustomerService.delete(id);
      notificationManager.success(
        'Cliente excluído',
        'Cliente removido com sucesso'
      );
      loadCustomers();
    } catch (error) {
      const message =
        error instanceof Error ? error.message : 'Erro ao excluir cliente';
      notificationManager.error('Erro ao excluir cliente', message);
    }
  };

  return (
    <div className='space-y-6'>
      {/* Header */}
      <div className='md:flex md:items-center md:justify-between'>
        <div className='flex-1 min-w-0'>
          <h1 className='text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate'>
            Clientes
          </h1>
          <p className='mt-1 text-sm text-gray-500'>
            Gerencie sua base de clientes
          </p>
        </div>
        <div className='mt-4 flex md:mt-0 md:ml-4'>
          <Link to='/customers/new' className='btn-primary'>
            👥 Novo Cliente
          </Link>
        </div>
      </div>

      {/* Search */}
      <div className='card'>
        <div className='grid grid-cols-1 gap-4'>
          <div>
            <label className='form-label'>Buscar clientes</label>
            <input
              type='text'
              className='form-input'
              placeholder='Nome, email ou CPF do cliente...'
              value={filters.search}
              onChange={(e) => handleSearch(e.target.value)}
            />
          </div>
          <div className='flex items-center'>
            <input
              type='checkbox'
              id='include-deleted'
              className='h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded'
              checked={filters.include_deleted}
              onChange={(e) =>
                setFilters((prev) => ({
                  ...prev,
                  include_deleted: e.target.checked,
                  page: 1,
                }))
              }
            />
            <label
              htmlFor='include-deleted'
              className='ml-2 block text-sm text-gray-700'
            >
              Mostrar clientes excluídos
            </label>
          </div>
        </div>
      </div>

      {/* Customers List */}
      <div className='card'>
        {isLoading ? (
          <div className='py-12'>
            <LoadingSpinner size='lg' text='Carregando clientes...' />
          </div>
        ) : Array.isArray(customers) && customers.length === 0 ? (
          <div className='text-center py-12'>
            <div className='text-6xl mb-4'>👥</div>
            <h3 className='text-lg font-medium text-gray-900 mb-2'>
              Nenhum cliente encontrado
            </h3>
            <p className='text-gray-500 mb-6'>
              {filters.search
                ? 'Nenhum cliente corresponde à busca realizada.'
                : 'Você ainda não cadastrou nenhum cliente.'}
            </p>
            <Link to='/customers/new' className='btn-primary'>
              Cadastrar primeiro cliente
            </Link>
          </div>
        ) : (
          <>
            <div className='overflow-x-auto'>
              <table className='table'>
                <thead className='bg-gray-50'>
                  <tr>
                    <th className='table-header'>Cliente</th>
                    <th className='table-header'>CPF</th>
                    <th className='table-header'>Telefone</th>
                    <th className='table-header'>Status</th>
                    <th className='table-header'>Cadastro</th>
                    <th className='table-header'>Ações</th>
                  </tr>
                </thead>
                <tbody className='divide-y divide-gray-200'>
                  {customers.map((customer) => (
                    <tr
                      key={customer.id}
                      className={`hover:bg-gray-50 ${customer.is_deleted ? 'opacity-60' : ''}`}
                    >
                      <td className='table-cell'>
                        <div className='flex items-center'>
                          <div className='flex-shrink-0 h-10 w-10'>
                            <div className='h-10 w-10 rounded-full bg-primary-500 flex items-center justify-center'>
                              <span className='text-white font-semibold'>
                                {customer.name.charAt(0).toUpperCase()}
                              </span>
                            </div>
                          </div>
                          <div className='ml-4'>
                            <div className='text-sm font-medium text-gray-900'>
                              {customer.name}
                              {customer.is_deleted && (
                                <span className='ml-2 text-xs text-red-500'>
                                  (Excluído)
                                </span>
                              )}
                            </div>
                            <div className='text-sm text-gray-500'>
                              {customer.email}
                            </div>
                          </div>
                        </div>
                      </td>
                      <td className='table-cell'>
                        <div className='text-sm text-gray-900 font-mono'>
                          {customer.cpf}
                        </div>
                      </td>
                      <td className='table-cell'>
                        <div className='text-sm text-gray-900'>
                          {customer.phone}
                        </div>
                      </td>
                      <td className='table-cell'>
                        {customer.is_deleted ? (
                          <span className='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800'>
                            Excluído
                          </span>
                        ) : (
                          <span className='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800'>
                            Ativo
                          </span>
                        )}
                      </td>
                      <td className='table-cell'>
                        <div className='text-sm text-gray-500'>
                          {formatDate(customer.created_at)}
                        </div>
                      </td>
                      <td className='table-cell'>
                        <div className='flex items-center space-x-3'>
                          <Link
                            to={`/customers/${customer.id}`}
                            className='text-primary-600 hover:text-primary-900 text-sm font-medium'
                          >
                            Ver
                          </Link>
                          {!customer.is_deleted && (
                            <>
                              <Link
                                to={`/customers/${customer.id}/edit`}
                                className='text-yellow-600 hover:text-yellow-900 text-sm font-medium'
                              >
                                Editar
                              </Link>
                              <button
                                onClick={() =>
                                  handleDeleteCustomer(customer.id)
                                }
                                className='text-red-600 hover:text-red-900 text-sm font-medium'
                              >
                                Excluir
                              </button>
                            </>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Pagination */}
            {pagination && pagination.last_page > 1 && (
              <div className='flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6'>
                <div className='flex flex-1 justify-between sm:hidden'>
                  <button
                    onClick={() =>
                      handlePageChange(pagination.current_page - 1)
                    }
                    disabled={pagination.current_page === 1}
                    className='btn-secondary disabled:opacity-50'
                  >
                    Anterior
                  </button>
                  <button
                    onClick={() =>
                      handlePageChange(pagination.current_page + 1)
                    }
                    disabled={pagination.current_page === pagination.last_page}
                    className='btn-secondary disabled:opacity-50'
                  >
                    Próximo
                  </button>
                </div>
                <div className='hidden sm:flex sm:flex-1 sm:items-center sm:justify-between'>
                  <div>
                    <p className='text-sm text-gray-700'>
                      Mostrando {pagination.from} até {pagination.to} de{' '}
                      <span className='font-medium'>{pagination.total}</span>{' '}
                      clientes
                    </p>
                  </div>
                  <div>
                    <nav className='isolate inline-flex -space-x-px rounded-md shadow-sm'>
                      {(() => {
                        const maxVisiblePages = 10;
                        const current = pagination.current_page;
                        const total = pagination.last_page;

                        // Se há 10 páginas ou menos, mostra todas
                        if (total <= maxVisiblePages) {
                          return Array.from({ length: total }, (_, i) => {
                            const page = i + 1;
                            const isActive = page === current;
                            return (
                              <button
                                key={page}
                                onClick={() => handlePageChange(page)}
                                className={`${
                                  isActive
                                    ? 'relative z-10 inline-flex items-center bg-blue-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600'
                                    : 'relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0'
                                }`}
                              >
                                {page}
                              </button>
                            );
                          });
                        }

                        // Para mais de 10 páginas, mostra range inteligente
                        const pages = [];
                        const startPage = Math.max(1, current - 4);
                        const endPage = Math.min(
                          total,
                          startPage + maxVisiblePages - 1
                        );

                        for (let page = startPage; page <= endPage; page++) {
                          const isActive = page === current;
                          pages.push(
                            <button
                              key={page}
                              onClick={() => handlePageChange(page)}
                              className={`${
                                isActive
                                  ? 'relative z-10 inline-flex items-center bg-blue-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600'
                                  : 'relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0'
                              }`}
                            >
                              {page}
                            </button>
                          );
                        }

                        return pages;
                      })()}
                    </nav>
                  </div>
                </div>
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
}

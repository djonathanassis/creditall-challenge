import { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import type { Sale, SalesFilters, SaleStatusType } from '../types/sale';
import { SaleStatus } from '../types/sale';
import SalesService from '../services/salesService';
import { LoadingSpinner } from '../components/Loading';
import { notificationManager } from '../utils/notificationManager';
import { formatCurrency, formatDate } from '../utils/formatters';
import type { PaginatedResponse } from '../types/api';

export default function SalesPage() {
  const [sales, setSales] = useState<Sale[]>([]);
  const [pagination, setPagination] = useState<
    PaginatedResponse<Sale>['meta'] | null
  >(null);
  const [isLoading, setIsLoading] = useState(true);
  const [filters, setFilters] = useState<SalesFilters>({
    page: 1,
    per_page: 15,
    status: undefined,
    include_deleted: false,
  });

  const loadSales = useCallback(async () => {
    try {
      setIsLoading(true);
      const response = await SalesService.getAll(filters);
      setSales(response.data);
      setPagination(response.meta);
    } catch (error) {
      console.error('Error loading sales:', error);
      notificationManager.error(
        'Erro ao carregar vendas',
        'Não foi possível carregar a lista de vendas'
      );
    } finally {
      setIsLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    loadSales();
  }, [loadSales]);

  const handleStatusFilter = (status: SaleStatusType | undefined) => {
    setFilters((prev) => ({ ...prev, status, page: 1 }));
  };

  const handlePageChange = (page: number) => {
    setFilters((prev) => ({ ...prev, page }));
  };

  const handleStatusUpdate = async (
    saleId: number,
    newStatus: SaleStatusType
  ) => {
    if (
      !window.confirm(
        `Tem certeza que deseja alterar o status desta venda para "${newStatus}"?`
      )
    ) {
      return;
    }

    try {
      await SalesService.updateStatus(saleId, newStatus);
      notificationManager.success(
        'Status atualizado',
        'Status da venda foi atualizado com sucesso'
      );
      loadSales();
    } catch (error) {
      const message =
        error instanceof Error ? error.message : 'Erro ao atualizar status';
      notificationManager.error('Erro ao atualizar status', message);
    }
  };

  const getStatusBadge = (status: SaleStatusType) => {
    switch (status) {
      case SaleStatus.PENDING:
        return <span className='badge-pending'>⏳ Pendente</span>;
      case SaleStatus.COMPLETED:
        return <span className='badge-completed'>✅ Pago</span>;
      case SaleStatus.CANCELLED:
        return <span className='badge-cancelled'>❌ Cancelado</span>;
      default:
        return <span className='badge'>{status}</span>;
    }
  };

  return (
    <div className='space-y-6'>
      {/* Header */}
      <div className='md:flex md:items-center md:justify-between'>
        <div className='flex-1 min-w-0'>
          <h1 className='text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate'>
            Vendas
          </h1>
          <p className='mt-1 text-sm text-gray-500'>
            Gerencie todas as vendas do sistema
          </p>
        </div>
        <div className='mt-4 flex md:mt-0 md:ml-4'>
          <Link to='/sales/new' className='btn-primary'>
            💰 Nova Venda
          </Link>
        </div>
      </div>

      {/* Filters */}
      <div className='card'>
        <div className='flex flex-wrap gap-3'>
          <button
            onClick={() => handleStatusFilter(undefined)}
            className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${
              filters.status === undefined
                ? 'bg-primary-100 text-primary-700 border border-primary-300'
                : 'bg-gray-100 text-gray-700 border border-gray-300 hover:bg-gray-200'
            }`}
          >
            Todas
          </button>
          <button
            onClick={() => handleStatusFilter(SaleStatus.PENDING)}
            className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${
              filters.status === SaleStatus.PENDING
                ? 'bg-yellow-100 text-yellow-700 border border-yellow-300'
                : 'bg-gray-100 text-gray-700 border border-gray-300 hover:bg-gray-200'
            }`}
          >
            ⏳ Pendentes
          </button>
          <button
            onClick={() => handleStatusFilter(SaleStatus.COMPLETED)}
            className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${
              filters.status === SaleStatus.COMPLETED
                ? 'bg-green-100 text-green-700 border border-green-300'
                : 'bg-gray-100 text-gray-700 border border-gray-300 hover:bg-gray-200'
            }`}
          >
            ✅ Pagas
          </button>
          <button
            onClick={() => handleStatusFilter(SaleStatus.CANCELLED)}
            className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${
              filters.status === SaleStatus.CANCELLED
                ? 'bg-red-100 text-red-700 border border-red-300'
                : 'bg-gray-100 text-gray-700 border border-gray-300 hover:bg-gray-200'
            }`}
          >
            ❌ Canceladas
          </button>
        </div>
        <div className='mt-4 flex items-center'>
          <input
            type='checkbox'
            id='include-deleted-sales'
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
            htmlFor='include-deleted-sales'
            className='ml-2 block text-sm text-gray-700'
          >
            Mostrar vendas excluídas
          </label>
        </div>
      </div>

      {/* Sales List */}
      <div className='card'>
        {isLoading ? (
          <div className='py-12'>
            <LoadingSpinner size='lg' text='Carregando vendas...' />
          </div>
        ) : sales.length === 0 ? (
          <div className='text-center py-12'>
            <div className='text-6xl mb-4'>💰</div>
            <h3 className='text-lg font-medium text-gray-900 mb-2'>
              Nenhuma venda encontrada
            </h3>
            <p className='text-gray-500 mb-6'>
              {filters.status
                ? `Nenhuma venda com status "${filters.status}" encontrada.`
                : 'Você ainda não registrou nenhuma venda.'}
            </p>
            <Link to='/sales/new' className='btn-primary'>
              Registrar primeira venda
            </Link>
          </div>
        ) : (
          <>
            <div className='overflow-x-auto'>
              <table className='table'>
                <thead className='bg-gray-50'>
                  <tr>
                    <th className='table-header'>ID</th>
                    <th className='table-header'>Cliente</th>
                    <th className='table-header'>Total</th>
                    <th className='table-header'>Status da Venda</th>
                    <th className='table-header'>Status do Registro</th>
                    <th className='table-header'>Data</th>
                    <th className='table-header'>Ações</th>
                  </tr>
                </thead>
                <tbody className='divide-y divide-gray-200'>
                  {sales.map((sale) => (
                    <tr
                      key={sale.id}
                      className={`hover:bg-gray-50 ${sale.is_deleted ? 'opacity-60' : ''}`}
                    >
                      <td className='table-cell'>
                        <div className='text-sm font-mono text-gray-900'>
                          #{sale.id.toString().padStart(4, '0')}
                          {sale.is_deleted && (
                            <span className='ml-1 text-xs text-red-500'>
                              (Excluída)
                            </span>
                          )}
                        </div>
                      </td>
                      <td className='table-cell'>
                        <div className='text-sm font-medium text-gray-900'>
                          {sale.customer?.name || 'Cliente não informado'}
                        </div>
                        {sale.customer?.email && (
                          <div className='text-sm text-gray-500'>
                            {sale.customer.email}
                          </div>
                        )}
                      </td>
                      <td className='table-cell'>
                        <div className='text-sm font-bold text-gray-900'>
                          {formatCurrency(Number(sale.total_amount))}
                        </div>
                        {sale.discount_percentage &&
                          Number(sale.discount_percentage) > 0 && (
                            <div className='text-xs text-green-600'>
                              {sale.discount_percentage}% desconto
                            </div>
                          )}
                      </td>
                      <td className='table-cell'>
                        {getStatusBadge(sale.status)}
                      </td>
                      <td className='table-cell'>
                        {sale.is_deleted ? (
                          <span className='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800'>
                            Excluída
                          </span>
                        ) : (
                          <span className='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800'>
                            Ativa
                          </span>
                        )}
                      </td>
                      <td className='table-cell'>
                        <div className='text-sm text-gray-500'>
                          {formatDate(sale.created_at)}
                        </div>
                      </td>
                      <td className='table-cell'>
                        <div className='flex items-center space-x-2'>
                          <Link
                            to={`/sales/${sale.id}`}
                            className='text-primary-600 hover:text-primary-900 text-sm font-medium'
                          >
                            Ver
                          </Link>

                          {/* Status change buttons - only for non-deleted sales */}
                          {!sale.is_deleted && (
                            <>
                              {sale.status === SaleStatus.PENDING && (
                                <>
                                  <button
                                    onClick={() =>
                                      handleStatusUpdate(
                                        sale.id,
                                        SaleStatus.COMPLETED
                                      )
                                    }
                                    className='text-green-600 hover:text-green-900 text-sm font-medium'
                                  >
                                    Pagar
                                  </button>
                                  <button
                                    onClick={() =>
                                      handleStatusUpdate(
                                        sale.id,
                                        SaleStatus.CANCELLED
                                      )
                                    }
                                    className='text-red-600 hover:text-red-900 text-sm font-medium'
                                  >
                                    Cancelar
                                  </button>
                                </>
                              )}

                              {sale.status === SaleStatus.COMPLETED && (
                                <button
                                  onClick={() =>
                                    handleStatusUpdate(
                                      sale.id,
                                      SaleStatus.CANCELLED
                                    )
                                  }
                                  className='text-red-600 hover:text-red-900 text-sm font-medium'
                                >
                                  Cancelar
                                </button>
                              )}

                              {sale.status === SaleStatus.CANCELLED && (
                                <button
                                  onClick={() =>
                                    handleStatusUpdate(
                                      sale.id,
                                      SaleStatus.PENDING
                                    )
                                  }
                                  className='text-yellow-600 hover:text-yellow-900 text-sm font-medium'
                                >
                                  Reativar
                                </button>
                              )}
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
                      vendas
                    </p>
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

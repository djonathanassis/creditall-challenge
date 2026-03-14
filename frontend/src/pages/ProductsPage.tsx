import { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import type { Product, ProductFilters } from '../types/product';
import ProductService from '../services/productService';
import { LoadingSpinner } from '../components/Loading';
import { notificationManager } from '../utils/notificationManager';
import { formatCurrency } from '../utils/formatters';
import type { PaginatedResponse } from '../types/api';

export default function ProductsPage() {
  const [products, setProducts] = useState<Product[]>([]);
  const [pagination, setPagination] = useState<
    PaginatedResponse<Product>['meta'] | null
  >(null);
  const [isLoading, setIsLoading] = useState(true);
  const [filters, setFilters] = useState<ProductFilters>({
    page: 1,
    per_page: 15,
    search: '',
    price_min: undefined,
    price_max: undefined,
    include_deleted: false,
  });

  const loadProducts = useCallback(async () => {
    try {
      setIsLoading(true);
      const response = await ProductService.getAll(filters);

      if (response && Array.isArray(response.data)) {
        setProducts(response.data);
        setPagination(response.meta);
      } else {
        console.warn('Invalid response format:', response);
        setProducts([]);
        setPagination(null);
      }
    } catch (error) {
      console.error('Error loading products:', error);
      setProducts([]);
      setPagination(null);
      notificationManager.error(
        'Erro ao carregar produtos',
        'Não foi possível carregar a lista de produtos'
      );
    } finally {
      setIsLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    loadProducts();
  }, [loadProducts]);

  const handleSearch = (search: string) => {
    setFilters((prev) => ({ ...prev, search, page: 1 }));
  };

  const handlePriceFilter = (minPrice?: number, maxPrice?: number) => {
    setFilters((prev) => ({
      ...prev,
      price_min: minPrice,
      price_max: maxPrice,
      page: 1,
    }));
  };

  const handlePageChange = (page: number) => {
    setFilters((prev) => ({ ...prev, page }));
  };

  const handleDeleteProduct = async (id: number) => {
    if (!window.confirm('Tem certeza que deseja excluir este produto?')) {
      return;
    }

    try {
      await ProductService.delete(id);
      notificationManager.success(
        'Produto excluído',
        'Produto removido com sucesso'
      );
      loadProducts();
    } catch (error) {
      const message =
        error instanceof Error ? error.message : 'Erro ao excluir produto';
      notificationManager.error('Erro ao excluir produto', message);
    }
  };

  const getStockStatus = (quantity: number) => {
    if (quantity === 0) {
      return { text: 'Esgotado', class: 'badge-cancelled' };
    } else if (quantity <= 5) {
      return { text: 'Estoque Baixo', class: 'badge-pending' };
    } else {
      return { text: 'Em Estoque', class: 'badge-completed' };
    }
  };

  return (
    <div className='space-y-6'>
      {/* Header */}
      <div className='md:flex md:items-center md:justify-between'>
        <div className='flex-1 min-w-0'>
          <h1 className='text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate'>
            Produtos
          </h1>
          <p className='mt-1 text-sm text-gray-500'>
            Gerencie seu catálogo de produtos
          </p>
        </div>
        <div className='mt-4 flex md:mt-0 md:ml-4'>
          <Link to='/products/new' className='btn-primary'>
            📦 Novo Produto
          </Link>
        </div>
      </div>

      {/* Filters */}
      <div className='card'>
        <div className='grid grid-cols-1 gap-4 md:grid-cols-4'>
          <div className='md:col-span-2'>
            <label className='form-label'>Buscar produtos</label>
            <input
              type='text'
              className='form-input'
              placeholder='Nome do produto...'
              value={filters.search}
              onChange={(e) => handleSearch(e.target.value)}
            />
          </div>
          <div>
            <label className='form-label'>Preço mínimo</label>
            <input
              type='number'
              className='form-input'
              placeholder='R$ 0,00'
              min='0'
              step='0.01'
              onChange={(e) =>
                handlePriceFilter(
                  e.target.value ? Number(e.target.value) : undefined,
                  filters.price_max
                )
              }
            />
          </div>
          <div>
            <label className='form-label'>Preço máximo</label>
            <input
              type='number'
              className='form-input'
              placeholder='R$ 9999,99'
              min='0'
              step='0.01'
              onChange={(e) =>
                handlePriceFilter(
                  filters.price_min,
                  e.target.value ? Number(e.target.value) : undefined
                )
              }
            />
          </div>
        </div>
        <div className='mt-4 flex items-center'>
          <input
            type='checkbox'
            id='include-deleted-products'
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
            htmlFor='include-deleted-products'
            className='ml-2 block text-sm text-gray-700'
          >
            Mostrar produtos excluídos
          </label>
        </div>
      </div>

      {/* Products List */}
      <div className='card'>
        {isLoading ? (
          <div className='py-12'>
            <LoadingSpinner size='lg' text='Carregando produtos...' />
          </div>
        ) : Array.isArray(products) && products.length === 0 ? (
          <div className='text-center py-12'>
            <div className='text-6xl mb-4'>📦</div>
            <h3 className='text-lg font-medium text-gray-900 mb-2'>
              Nenhum produto encontrado
            </h3>
            <p className='text-gray-500 mb-6'>
              {filters.search || filters.price_min || filters.price_max
                ? 'Nenhum produto corresponde aos filtros aplicados.'
                : 'Você ainda não cadastrou nenhum produto.'}
            </p>
            <Link to='/products/new' className='btn-primary'>
              Cadastrar primeiro produto
            </Link>
          </div>
        ) : (
          <>
            <div className='overflow-x-auto'>
              <table className='table'>
                <thead className='bg-gray-50'>
                  <tr>
                    <th className='table-header'>Produto</th>
                    <th className='table-header'>Preço</th>
                    <th className='table-header'>Estoque</th>
                    <th className='table-header'>Status do Produto</th>
                    <th className='table-header'>Status do Estoque</th>
                    <th className='table-header'>Ações</th>
                  </tr>
                </thead>
                <tbody className='divide-y divide-gray-200'>
                  {products.map((product) => {
                    const stockStatus = getStockStatus(product.stock_quantity);
                    return (
                      <tr
                        key={product.id}
                        className={`hover:bg-gray-50 ${product.is_deleted ? 'opacity-60' : ''}`}
                      >
                        <td className='table-cell'>
                          <div className='flex items-center'>
                            <div className='flex-shrink-0 h-12 w-12'>
                              {product.image_url ? (
                                <img
                                  className='h-12 w-12 rounded-lg object-cover'
                                  src={product.image_url}
                                  alt={product.name}
                                />
                              ) : (
                                <div className='h-12 w-12 rounded-lg bg-gray-200 flex items-center justify-center'>
                                  📦
                                </div>
                              )}
                            </div>
                            <div className='ml-4'>
                              <div className='text-sm font-medium text-gray-900'>
                                {product.name}
                                {product.is_deleted && (
                                  <span className='ml-2 text-xs text-red-500'>
                                    (Excluído)
                                  </span>
                                )}
                              </div>
                              <div className='text-sm text-gray-500 truncate max-w-xs'>
                                {product.description}
                              </div>
                            </div>
                          </div>
                        </td>
                        <td className='table-cell'>
                          <div className='text-sm font-medium text-gray-900'>
                            {formatCurrency(Number(product.price))}
                          </div>
                        </td>
                        <td className='table-cell'>
                          <div className='text-sm text-gray-900'>
                            {product.stock_quantity} un
                          </div>
                        </td>
                        <td className='table-cell'>
                          {product.is_deleted ? (
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
                          <span className={`badge ${stockStatus.class}`}>
                            {stockStatus.text}
                          </span>
                        </td>
                        <td className='table-cell'>
                          <div className='flex items-center space-x-3'>
                            {!product.is_deleted && (
                              <>
                                <Link
                                  to={`/products/${product.id}/edit`}
                                  className='text-yellow-600 hover:text-yellow-900 text-sm font-medium'
                                >
                                  Editar
                                </Link>
                                <button
                                  onClick={() =>
                                    handleDeleteProduct(product.id)
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
                    );
                  })}
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
                      produtos
                    </p>
                  </div>
                  <div>
                    <nav className='isolate inline-flex -space-x-px rounded-md shadow-sm'>
                      {Array.from({ length: pagination.last_page }, (_, i) => {
                        const page = i + 1;
                        const isActive = page === pagination.current_page;
                        return (
                          <button
                            key={page}
                            onClick={() => handlePageChange(page)}
                            className={`${
                              isActive
                                ? 'bg-primary-50 border-primary-500 text-primary-600'
                                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                            } relative inline-flex items-center px-4 py-2 text-sm font-medium border ${
                              i === 0 ? 'rounded-l-md' : ''
                            } ${i === pagination.last_page - 1 ? 'rounded-r-md' : ''}`}
                          >
                            {page}
                          </button>
                        );
                      })}
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

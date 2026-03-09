import { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { type Customer } from '../types/customer.ts';
import { type Product } from '../types/product.ts';
import { type CreateSaleRequest, type SaleItem } from '../types/sale.ts';
import CustomerService from '../services/customerService.ts';
import ProductService from '../services/productService.ts';
import SalesService from '../services/salesService.ts';
import { notificationManager } from '../utils/notificationManager.ts';
import { formatCurrency } from '../utils/formatters.ts';

type FormSaleItem = Omit<
  SaleItem,
  'id' | 'sale_id' | 'subtotal' | 'is_deleted' | 'deleted_at'
> & {
  id?: number;
  sale_id?: number;
  subtotal?: string;
};

export default function SalesFormPage() {
  const navigate = useNavigate();

  const [selectedCustomerId, setSelectedCustomerId] = useState<number | null>(
    null
  );
  const [discountPercentage, setDiscountPercentage] = useState<number>(0);
  const [items, setItems] = useState<FormSaleItem[]>([]);

  const [customers, setCustomers] = useState<Customer[]>([]);
  const [products, setProducts] = useState<Product[]>([]);
  const [loadingCustomers, setLoadingCustomers] = useState(true);
  const [loadingProducts, setLoadingProducts] = useState(true);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    loadCustomers();
    loadProducts();
  }, []);

  const loadCustomers = async () => {
    try {
      setLoadingCustomers(true);
      const data = await CustomerService.getAll();
      setCustomers(data.data);
    } catch (error) {
      console.error('Error loading customers:', error);
      notificationManager.error(
        'Erro ao carregar clientes',
        'Não foi possível carregar a lista de clientes'
      );
    } finally {
      setLoadingCustomers(false);
    }
  };

  const loadProducts = async () => {
    try {
      setLoadingProducts(true);
      const data = await ProductService.getAll();
      setProducts(data.data);
    } catch (error) {
      console.error('Error loading products:', error);
      notificationManager.error(
        'Erro ao carregar produtos',
        'Não foi possível carregar a lista de produtos'
      );
    } finally {
      setLoadingProducts(false);
    }
  };

  const addItem = () => {
    const newItem: FormSaleItem = {
      product_id: 0,
      quantity: 1,
      unit_price: '0',
    };
    setItems([...items, newItem]);
  };

  const removeItem = (index: number) => {
    setItems(items.filter((_, i) => i !== index));
  };

  const updateItem = (
    index: number,
    field: keyof FormSaleItem,
    value: number
  ) => {
    const updatedItems = [...items];
    updatedItems[index] = { ...updatedItems[index], [field]: value };

    if (field === 'product_id') {
      const selectedProduct = products.find((p) => p.id === value);
      if (selectedProduct) {
        updatedItems[index].unit_price = selectedProduct.price;
      }
    }

    setItems(updatedItems);
  };

  const calculateSubtotal = (): number => {
    return items.reduce(
      (total, item) => total + item.quantity * Number(item.unit_price),
      0
    );
  };

  const calculateDiscountAmount = (): number => {
    const subtotal = calculateSubtotal();
    return (subtotal * discountPercentage) / 100;
  };

  const calculateTotal = (): number => {
    return calculateSubtotal() - calculateDiscountAmount();
  };

  const validateForm = (): string | null => {
    if (!selectedCustomerId) {
      return 'Selecione um cliente';
    }

    if (items.length === 0) {
      return 'Adicione pelo menos um produto';
    }

    for (let i = 0; i < items.length; i++) {
      const item = items[i];
      if (!item.product_id) {
        return `Selecione o produto do item ${i + 1}`;
      }
      if (item.quantity <= 0) {
        return `A quantidade do item ${i + 1} deve ser maior que zero`;
      }

      const product = products.find((p) => p.id === item.product_id);
      if (product && item.quantity > product.stock_quantity) {
        return `Estoque insuficiente para ${product.name}. Disponível: ${product.stock_quantity}`;
      }
    }

    if (discountPercentage < 0 || discountPercentage > 100) {
      return 'O desconto deve estar entre 0% e 100%';
    }

    return null;
  };

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();

    const validationError = validateForm();
    if (validationError) {
      notificationManager.error('Erro na validação', validationError);
      return;
    }

    try {
      setSubmitting(true);

      const saleData: CreateSaleRequest = {
        customer_id: selectedCustomerId!,
        discount_percentage: discountPercentage,
        items: items,
      };

      await SalesService.create(saleData);
      notificationManager.success(
        'Venda registrada com sucesso!',
        'A venda foi criada e está com status pendente'
      );
      navigate('/sales');
    } catch (error) {
      console.error('Error creating sale:', error);
      notificationManager.error(
        'Erro ao registrar venda',
        'Não foi possível criar a venda'
      );
    } finally {
      setSubmitting(false);
    }
  };

  if (loadingCustomers || loadingProducts) {
    return (
      <div className='space-y-6'>
        <div className='flex items-center justify-center py-12'>
          <div className='animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600'></div>
          <span className='ml-3 text-gray-600'>Carregando dados...</span>
        </div>
      </div>
    );
  }

  return (
    <div className='space-y-6'>
      {/* Header */}
      <div className='md:flex md:items-center md:justify-between'>
        <div className='flex-1 min-w-0'>
          <h1 className='text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate'>
            Nova Venda
          </h1>
          <p className='mt-1 text-sm text-gray-500'>
            Registre uma nova venda no sistema
          </p>
        </div>
        <div className='mt-4 flex md:mt-0 md:ml-4'>
          <Link to='/sales' className='btn-secondary mr-3'>
            Voltar para Vendas
          </Link>
        </div>
      </div>

      {/* Form */}
      <form onSubmit={handleSubmit} className='space-y-6'>
        <div className='card'>
          <div className='space-y-6'>
            {/* Customer Selection */}
            <div>
              <label
                htmlFor='customer'
                className='block text-sm font-medium text-gray-700 mb-2'
              >
                Cliente *
              </label>
              <select
                id='customer'
                value={selectedCustomerId || ''}
                onChange={(e) =>
                  setSelectedCustomerId(
                    e.target.value ? Number(e.target.value) : null
                  )
                }
                className='input-field'
                required
              >
                <option value=''>Selecione um cliente</option>
                {customers.map((customer) => (
                  <option key={customer.id} value={customer.id}>
                    {customer.name} - {customer.cpf}
                  </option>
                ))}
              </select>
            </div>

            {/* Items Section */}
            <div>
              <div className='flex items-center justify-between mb-4'>
                <label className='block text-sm font-medium text-gray-700'>
                  Produtos *
                </label>
                <button
                  type='button'
                  onClick={addItem}
                  className='btn-secondary'
                >
                  + Adicionar Produto
                </button>
              </div>

              {items.length === 0 ? (
                <div className='text-center py-8 text-gray-500'>
                  Nenhum produto adicionado. Clique em "Adicionar Produto" para
                  começar.
                </div>
              ) : (
                <div className='space-y-4'>
                  {items.map((item, index) => (
                    <div
                      key={index}
                      className='border border-gray-200 rounded-lg p-4'
                    >
                      <div className='grid grid-cols-1 md:grid-cols-5 gap-4 items-end'>
                        {/* Product Selection */}
                        <div className='md:col-span-2'>
                          <label className='block text-sm font-medium text-gray-700 mb-1'>
                            Produto
                          </label>
                          <select
                            value={item.product_id}
                            onChange={(e) =>
                              updateItem(
                                index,
                                'product_id',
                                Number(e.target.value)
                              )
                            }
                            className='input-field'
                            required
                          >
                            <option value={0}>Selecione um produto</option>
                            {products.map((product) => (
                              <option key={product.id} value={product.id}>
                                {product.name} - {formatCurrency(product.price)}{' '}
                                (Estoque: {product.stock_quantity})
                              </option>
                            ))}
                          </select>
                        </div>

                        {/* Quantity */}
                        <div>
                          <label className='block text-sm font-medium text-gray-700 mb-1'>
                            Quantidade
                          </label>
                          <input
                            type='number'
                            min='1'
                            value={item.quantity}
                            onChange={(e) =>
                              updateItem(
                                index,
                                'quantity',
                                Number(e.target.value)
                              )
                            }
                            className='input-field'
                            required
                          />
                        </div>

                        {/* Unit Price (readonly) */}
                        <div>
                          <label className='block text-sm font-medium text-gray-700 mb-1'>
                            Preço Unitário
                          </label>
                          <input
                            type='text'
                            value={formatCurrency(item.unit_price)}
                            readOnly
                            className='input-field bg-gray-50'
                          />
                        </div>

                        {/* Remove Button */}
                        <div>
                          <button
                            type='button'
                            onClick={() => removeItem(index)}
                            className='w-full btn-danger'
                          >
                            Remover
                          </button>
                        </div>
                      </div>

                      {/* Subtotal for this item */}
                      <div className='mt-3 pt-3 border-t border-gray-100'>
                        <div className='flex justify-between items-center'>
                          <span className='text-sm font-medium text-gray-700'>
                            Subtotal do Item:
                          </span>
                          <span className='text-lg font-semibold text-gray-900'>
                            {formatCurrency(
                              item.quantity * Number(item.unit_price)
                            )}
                          </span>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Discount */}
            <div>
              <label
                htmlFor='discount'
                className='block text-sm font-medium text-gray-700 mb-2'
              >
                Desconto (%)
              </label>
              <input
                type='number'
                id='discount'
                min='0'
                max='100'
                step='0.01'
                value={discountPercentage}
                onChange={(e) => setDiscountPercentage(Number(e.target.value))}
                className='input-field max-w-xs'
                placeholder='0.00'
              />
            </div>
          </div>
        </div>

        {/* Totals Summary */}
        {items.length > 0 && (
          <div className='card'>
            <h3 className='text-lg font-medium text-gray-900 mb-4'>
              Resumo da Venda
            </h3>
            <div className='space-y-2'>
              <div className='flex justify-between'>
                <span className='text-gray-600'>Subtotal:</span>
                <span className='font-medium'>
                  {formatCurrency(calculateSubtotal())}
                </span>
              </div>
              {discountPercentage > 0 && (
                <div className='flex justify-between'>
                  <span className='text-gray-600'>
                    Desconto ({discountPercentage}%):
                  </span>
                  <span className='font-medium text-red-600'>
                    -{formatCurrency(calculateDiscountAmount())}
                  </span>
                </div>
              )}
              <hr className='my-2' />
              <div className='flex justify-between text-lg font-semibold'>
                <span>Total:</span>
                <span>{formatCurrency(calculateTotal())}</span>
              </div>
            </div>
          </div>
        )}

        {/* Actions */}
        <div className='flex justify-end space-x-3'>
          <Link to='/sales' className='btn-secondary'>
            Cancelar
          </Link>
          <button
            type='submit'
            disabled={submitting || items.length === 0}
            className='btn-primary disabled:opacity-50 disabled:cursor-not-allowed'
          >
            {submitting ? 'Registrando...' : 'Registrar Venda'}
          </button>
        </div>
      </form>
    </div>
  );
}

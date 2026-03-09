import { useState, useEffect, useCallback } from 'react';
import type { FormEvent, ChangeEvent } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import type {
  Product,
  CreateProductRequest,
  UpdateProductRequest,
} from '../types/product';
import ProductService from '../services/productService';
import { LoadingSpinner } from '../components/Loading';
import { notificationManager } from '../utils/notificationManager';
import { formatCurrency } from '../utils/formatters';

interface ProductFormData {
  name: string;
  description: string;
  price: string;
  stock_quantity: string;
  image: File | null;
}

export default function ProductFormPage() {
  const navigate = useNavigate();
  const { id } = useParams<{ id: string }>();
  const isEditing = !!id;

  const [product, setProduct] = useState<Product | null>(null);
  const [isLoading, setIsLoading] = useState(isEditing);
  const [isSaving, setIsSaving] = useState(false);
  const [imagePreview, setImagePreview] = useState<string | null>(null);

  const [formData, setFormData] = useState<ProductFormData>({
    name: '',
    description: '',
    price: '',
    stock_quantity: '',
    image: null,
  });

  const [errors, setErrors] = useState<Record<string, string>>({});

  const loadProduct = useCallback(
    async (productId: number) => {
      try {
        setIsLoading(true);
        const productData = await ProductService.getById(productId);
        setProduct(productData);

        setFormData({
          name: productData.name,
          description: productData.description || '',
          price: productData.price,
          stock_quantity: productData.stock_quantity.toString(),
          image: null,
        });

        if (productData.image_url) {
          setImagePreview(productData.image_url);
        }
      } catch (error) {
        const message =
          error instanceof Error ? error.message : 'Erro ao carregar produto';
        notificationManager.error('Erro ao carregar produto', message);
        navigate('/products');
      } finally {
        setIsLoading(false);
      }
    },
    [navigate]
  );

  useEffect(() => {
    if (isEditing && id) {
      loadProduct(Number(id));
    }
  }, [id, isEditing, loadProduct]);

  const handleInputChange = (
    e: ChangeEvent<HTMLInputElement | HTMLTextAreaElement>
  ) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));

    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: '' }));
    }
  };

  const handleImageChange = (e: ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0] || null;
    setFormData((prev) => ({ ...prev, image: file }));

    if (file) {
      const reader = new FileReader();
      reader.onload = (e) => {
        setImagePreview(e.target?.result as string);
      };
      reader.readAsDataURL(file);
    } else {
      setImagePreview(product?.image_url || null);
    }

    if (errors.image) {
      setErrors((prev) => ({ ...prev, image: '' }));
    }
  };

  const validateForm = (): boolean => {
    const newErrors: Record<string, string> = {};

    if (!formData.name.trim()) {
      newErrors.name = 'Nome é obrigatório';
    }

    const price = parseFloat(formData.price);
    if (!formData.price || isNaN(price) || price <= 0) {
      newErrors.price = 'Preço deve ser um valor válido maior que zero';
    }

    const stock = parseInt(formData.stock_quantity);
    if (!formData.stock_quantity || isNaN(stock) || stock < 0) {
      newErrors.stock_quantity =
        'Quantidade em estoque deve ser um número válido';
    }

    if (formData.image) {
      const maxSize = 2 * 1024 * 1024; // 2MB
      const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

      if (formData.image.size > maxSize) {
        newErrors.image = 'Imagem deve ter no máximo 2MB';
      } else if (!allowedTypes.includes(formData.image.type)) {
        newErrors.image = 'Formato deve ser JPG, PNG ou GIF';
      }
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();

    if (!validateForm()) {
      return;
    }

    setIsSaving(true);

    try {
      const productData: CreateProductRequest | UpdateProductRequest = {
        name: formData.name.trim(),
        description: formData.description.trim() || null,
        price: parseFloat(formData.price),
        stock_quantity: parseInt(formData.stock_quantity),
        image: formData.image,
      };

      if (isEditing && product) {
        await ProductService.update(
          product.id,
          productData as UpdateProductRequest
        );
        notificationManager.success(
          'Produto atualizado',
          'Produto foi atualizado com sucesso'
        );
      } else {
        await ProductService.create(productData as CreateProductRequest);
        notificationManager.success(
          'Produto criado',
          'Produto foi criado com sucesso'
        );
      }

      navigate('/products');
    } catch (error) {
      const message =
        error instanceof Error ? error.message : 'Erro ao salvar produto';
      notificationManager.error('Erro ao salvar produto', message);
    } finally {
      setIsSaving(false);
    }
  };

  if (isLoading) {
    return (
      <div className='flex items-center justify-center h-64'>
        <LoadingSpinner size='lg' text='Carregando produto...' />
      </div>
    );
  }

  return (
    <div className='space-y-6'>
      {/* Header */}
      <div className='md:flex md:items-center md:justify-between'>
        <div className='flex-1 min-w-0'>
          <h1 className='text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate'>
            {isEditing ? 'Editar Produto' : 'Novo Produto'}
          </h1>
          <p className='mt-1 text-sm text-gray-500'>
            {isEditing
              ? 'Atualize as informações do produto'
              : 'Cadastre um novo produto no sistema'}
          </p>
        </div>
        <div className='mt-4 flex md:mt-0 md:ml-4'>
          <button
            type='button'
            onClick={() => navigate('/products')}
            className='btn-secondary mr-3'
          >
            Cancelar
          </button>
        </div>
      </div>

      {/* Form */}
      <div className='card'>
        <form onSubmit={handleSubmit} className='space-y-6'>
          <div className='grid grid-cols-1 gap-6 sm:grid-cols-2'>
            {/* Product Name */}
            <div className='sm:col-span-2'>
              <label htmlFor='name' className='form-label'>
                Nome do Produto *
              </label>
              <input
                type='text'
                name='name'
                id='name'
                className={`form-input ${errors.name ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : ''}`}
                placeholder='Digite o nome do produto'
                value={formData.name}
                onChange={handleInputChange}
                disabled={isSaving}
              />
              {errors.name && <p className='form-error'>{errors.name}</p>}
            </div>

            {/* Price */}
            <div>
              <label htmlFor='price' className='form-label'>
                Preço *
              </label>
              <div className='relative'>
                <div className='absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none'>
                  <span className='text-gray-500 sm:text-sm'>R$</span>
                </div>
                <input
                  type='number'
                  name='price'
                  id='price'
                  className={`form-input pl-12 ${errors.price ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : ''}`}
                  placeholder='0,00'
                  min='0'
                  step='0.01'
                  value={formData.price}
                  onChange={handleInputChange}
                  disabled={isSaving}
                />
              </div>
              {errors.price && <p className='form-error'>{errors.price}</p>}
              {formData.price && !errors.price && (
                <p className='mt-1 text-sm text-gray-500'>
                  Preço formatado:{' '}
                  {formatCurrency(parseFloat(formData.price) || 0)}
                </p>
              )}
            </div>

            {/* Stock Quantity */}
            <div>
              <label htmlFor='stock_quantity' className='form-label'>
                Quantidade em Estoque *
              </label>
              <input
                type='number'
                name='stock_quantity'
                id='stock_quantity'
                className={`form-input ${errors.stock_quantity ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : ''}`}
                placeholder='0'
                min='0'
                value={formData.stock_quantity}
                onChange={handleInputChange}
                disabled={isSaving}
              />
              {errors.stock_quantity && (
                <p className='form-error'>{errors.stock_quantity}</p>
              )}
            </div>

            {/* Description */}
            <div className='sm:col-span-2'>
              <label htmlFor='description' className='form-label'>
                Descrição
              </label>
              <textarea
                name='description'
                id='description'
                rows={4}
                className='form-input'
                placeholder='Descreva o produto (opcional)'
                value={formData.description}
                onChange={handleInputChange}
                disabled={isSaving}
              />
            </div>

            {/* Image Upload */}
            <div className='sm:col-span-2'>
              <label htmlFor='image' className='form-label'>
                Imagem do Produto
              </label>
              <div className='mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md'>
                <div className='space-y-1 text-center'>
                  {imagePreview ? (
                    <div className='mb-4'>
                      <img
                        src={imagePreview}
                        alt='Preview'
                        className='mx-auto h-32 w-32 object-cover rounded-lg'
                      />
                    </div>
                  ) : (
                    <svg
                      className='mx-auto h-12 w-12 text-gray-400'
                      stroke='currentColor'
                      fill='none'
                      viewBox='0 0 48 48'
                    >
                      <path
                        d='M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02'
                        strokeWidth={2}
                        strokeLinecap='round'
                        strokeLinejoin='round'
                      />
                    </svg>
                  )}
                  <div className='flex text-sm text-gray-600'>
                    <label
                      htmlFor='image'
                      className='relative cursor-pointer bg-white rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500'
                    >
                      <span>
                        {imagePreview ? 'Alterar imagem' : 'Fazer upload'}
                      </span>
                      <input
                        id='image'
                        name='image'
                        type='file'
                        className='sr-only'
                        accept='image/jpeg,image/png,image/gif'
                        onChange={handleImageChange}
                        disabled={isSaving}
                      />
                    </label>
                    <p className='pl-1'>ou arraste e solte</p>
                  </div>
                  <p className='text-xs text-gray-500'>PNG, JPG, GIF até 2MB</p>
                </div>
              </div>
              {errors.image && <p className='form-error'>{errors.image}</p>}
            </div>
          </div>

          {/* Form Actions */}
          <div className='flex justify-end space-x-3'>
            <button
              type='button'
              onClick={() => navigate('/products')}
              className='btn-secondary'
              disabled={isSaving}
            >
              Cancelar
            </button>
            <button type='submit' className='btn-primary' disabled={isSaving}>
              {isSaving ? (
                <>
                  <LoadingSpinner size='sm' />
                  <span className='ml-2'>
                    {isEditing ? 'Atualizando...' : 'Salvando...'}
                  </span>
                </>
              ) : (
                <span>
                  {isEditing ? 'Atualizar Produto' : 'Salvar Produto'}
                </span>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

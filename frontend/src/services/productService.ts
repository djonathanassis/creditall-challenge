import type {
  Product,
  CreateProductRequest,
  UpdateProductRequest,
  ProductFilters,
  ProductFormData,
} from '../types/product';
import type { PaginatedResponse } from '../types/api';
import {
  apiGet,
  apiGetPaginated,
  apiPost,
  apiPut,
  apiDelete,
  apiMultipart,
  handleApiError,
} from './api';
import { API_ENDPOINTS } from '../utils/constants';

export class ProductService {
  static async getAll(
    filters: ProductFilters = {}
  ): Promise<PaginatedResponse<Product>> {
    try {
      const response = await apiGetPaginated<Product>(
        API_ENDPOINTS.PRODUCTS.LIST,
        filters as Record<string, unknown>
      );
      return response;
    } catch (error) {
      const apiError = handleApiError(error);
      throw new Error(apiError.message);
    }
  }

  static async getById(id: number): Promise<Product> {
    try {
      const product = await apiGet<Product>(API_ENDPOINTS.PRODUCTS.SHOW(id));
      return product;
    } catch (error) {
      const apiError = handleApiError(error);

      if (apiError.message.includes('404')) {
        throw new Error('Produto não encontrado');
      }

      throw new Error(apiError.message);
    }
  }

  static async create(data: CreateProductRequest): Promise<Product> {
    try {
      if (data.image) {
        const formData: ProductFormData = {
          name: data.name,
          description: data.description || '',
          price: String(data.price),
          stock_quantity: String(data.stock_quantity),
          image: data.image,
        };

        return await apiMultipart<Product>(
          API_ENDPOINTS.PRODUCTS.CREATE,
          formData as unknown as Record<string, unknown>
        );
      }

      const jsonData = {
        name: data.name,
        description: data.description,
        price: data.price,
        stock_quantity: data.stock_quantity,
      };
      return await apiPost<Product>(API_ENDPOINTS.PRODUCTS.CREATE, jsonData);
    } catch (error) {
      const apiError = handleApiError(error);

      if (apiError.errors) {
        const validationMessages = Object.values(apiError.errors).flat();
        throw new Error(validationMessages.join(', '));
      }

      throw new Error(apiError.message);
    }
  }

  static async update(
    id: number,
    data: UpdateProductRequest
  ): Promise<Product> {
    try {
      if (data.image) {
        const formData: Partial<ProductFormData> = {};

        if (data.name !== undefined) formData.name = data.name;
        if (data.description !== undefined)
          formData.description = data.description || '';
        if (data.price !== undefined) formData.price = String(data.price);
        if (data.stock_quantity !== undefined)
          formData.stock_quantity = String(data.stock_quantity);
        if (data.image) formData.image = data.image;

        return await apiMultipart<Product>(
          API_ENDPOINTS.PRODUCTS.UPDATE(id),
          formData,
          'PUT'
        );
      }

      const jsonData: Partial<UpdateProductRequest> = {};
      if (data.name !== undefined) jsonData.name = data.name;
      if (data.description !== undefined)
        jsonData.description = data.description;
      if (data.price !== undefined) jsonData.price = data.price;
      if (data.stock_quantity !== undefined)
        jsonData.stock_quantity = data.stock_quantity;

      return await apiPut<Product>(API_ENDPOINTS.PRODUCTS.UPDATE(id), jsonData);
    } catch (error) {
      const apiError = handleApiError(error);

      if (apiError.message.includes('404')) {
        throw new Error('Produto não encontrado');
      }

      if (apiError.errors) {
        const validationMessages = Object.values(apiError.errors).flat();
        throw new Error(validationMessages.join(', '));
      }

      throw new Error(apiError.message);
    }
  }

  static async delete(id: number): Promise<void> {
    try {
      await apiDelete(API_ENDPOINTS.PRODUCTS.DELETE(id));
    } catch (error) {
      const apiError = handleApiError(error);

      if (apiError.message.includes('404')) {
        throw new Error('Produto não encontrado');
      }

      if (apiError.message.includes('409')) {
        throw new Error('Não é possível excluir produto com vendas associadas');
      }

      throw new Error(apiError.message);
    }
  }

  static async search(
    query: string,
    filters: Omit<ProductFilters, 'search'> = {}
  ): Promise<PaginatedResponse<Product>> {
    return this.getAll({
      ...filters,
      search: query,
    });
  }

  static async filterByPrice(
    minPrice?: number,
    maxPrice?: number,
    otherFilters: Omit<ProductFilters, 'price_min' | 'price_max'> = {}
  ): Promise<PaginatedResponse<Product>> {
    return this.getAll({
      ...otherFilters,
      price_min: minPrice,
      price_max: maxPrice,
    });
  }

  static async getLowStockProducts(threshold: number = 5): Promise<Product[]> {
    const response = await this.getAll({ per_page: 100 });
    return response.data.filter(
      (product) => product.stock_quantity < threshold
    );
  }

  static validateProductData(
    data: CreateProductRequest | UpdateProductRequest
  ): string[] {
    const errors: string[] = [];

    if ('name' in data && data.name !== undefined) {
      if (!data.name || data.name.trim().length === 0) {
        errors.push('Nome do produto é obrigatório');
      } else if (data.name.length > 255) {
        errors.push('Nome do produto não pode ter mais que 255 caracteres');
      }
    }

    if ('price' in data && data.price !== undefined) {
      if (data.price <= 0) {
        errors.push('Preço deve ser maior que zero');
      } else if (data.price > 999999.99) {
        errors.push('Preço não pode ser maior que R$ 999.999,99');
      }
    }

    if ('stock_quantity' in data && data.stock_quantity !== undefined) {
      if (data.stock_quantity < 0) {
        errors.push('Quantidade em estoque não pode ser negativa');
      }
    }

    if ('image' in data && data.image) {
      const maxSize = 2 * 1024 * 1024; // 2MB
      const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

      if (data.image.size > maxSize) {
        errors.push('Imagem deve ter no máximo 2MB');
      }

      if (!allowedTypes.includes(data.image.type)) {
        errors.push('Formato de imagem deve ser JPG, PNG ou GIF');
      }
    }

    return errors;
  }
}

export const {
  getAll: getAllProducts,
  getById: getProductById,
  create: createProduct,
  update: updateProduct,
  delete: deleteProduct,
  search: searchProducts,
  filterByPrice: filterProductsByPrice,
  getLowStockProducts,
  validateProductData,
} = ProductService;

export default ProductService;

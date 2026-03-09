import type { PaginationParams, SearchParams, SoftDeleteFilters } from './api';

export interface Product {
  id: number;
  name: string;
  description: string | null;
  price: string;
  stock_quantity: number;
  image_url: string | null;
  created_at: string;
  updated_at: string;
  is_deleted: boolean;
  deleted_at: string | null;
}

export interface CreateProductRequest {
  name: string;
  description?: string | null;
  price: number;
  stock_quantity: number;
  image?: File | null;
}

export interface UpdateProductRequest {
  name?: string;
  description?: string | null;
  price?: number;
  stock_quantity?: number;
  image?: File | null;
}

export interface ProductFilters
  extends PaginationParams, SearchParams, SoftDeleteFilters {
  price_min?: number;
  price_max?: number;
}

export interface ProductFormData {
  name: string;
  description?: string;
  price: string;
  stock_quantity: string;
  image?: File;
}

export interface ProductValidationErrors {
  name?: string[];
  description?: string[];
  price?: string[];
  stock_quantity?: string[];
  image?: string[];
}

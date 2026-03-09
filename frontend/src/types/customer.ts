import type { PaginationParams, SearchParams, SoftDeleteFilters } from './api';

export interface Customer {
  id: number;
  name: string;
  email: string;
  cpf: string;
  phone: string;
  created_at: string;
  updated_at: string;
  is_deleted: boolean;
  deleted_at: string | null;
}

export interface CustomerWithStats extends Customer {
  sales_stats: {
    total_sales: number;
    total_amount: string;
    average_order_value: string;
    last_purchase: string | null;
  };
}

export interface CreateCustomerRequest {
  name: string;
  email: string;
  cpf: string;
  phone: string;
}

export interface UpdateCustomerRequest {
  name?: string;
  email?: string;
  cpf?: string;
  phone?: string;
}

export interface CustomerFilters
  extends PaginationParams, SearchParams, SoftDeleteFilters {}

export interface CustomerValidationErrors {
  name?: string[];
  email?: string[];
  cpf?: string[];
  phone?: string[];
}

export interface CpfValidation {
  isValid: boolean;
  formatted: string;
  cleaned: string;
  error?: string;
}

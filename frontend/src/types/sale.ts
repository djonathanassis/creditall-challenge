import type { PaginationParams, SearchParams, SoftDeleteFilters } from './api';
import type { Customer } from './customer';
import type { Product } from './product';

export const SaleStatus = {
  PENDING: 'pending',
  COMPLETED: 'completed',
  CANCELLED: 'cancelled',
} as const;

export type SaleStatusType = (typeof SaleStatus)[keyof typeof SaleStatus];

export interface Sale {
  id: number;
  customer_id: number;
  status: SaleStatusType;
  subtotal_amount: string;
  discount_percentage: string | null;
  discount_amount: string;
  total_amount: string;
  created_at: string;
  updated_at: string;
  is_deleted: boolean;
  deleted_at: string | null;
  customer?: Customer;
}

export interface SaleItem {
  id: number;
  sale_id: number;
  product_id: number;
  quantity: number;
  unit_price: string;
  subtotal: string;
  is_deleted: boolean;
  deleted_at: string | null;
  product?: Product;
}

export interface SaleWithDetails extends Sale {
  customer: Customer;
  items: SaleItem[];
}

export interface CreateSaleRequest {
  customer_id: number;
  discount_percentage?: number | null;
  items: {
    product_id: number;
    quantity: number;
  }[];
}

export interface UpdateSaleStatusRequest {
  status: SaleStatusType;
}

export interface SalesFilters
  extends PaginationParams, SearchParams, SoftDeleteFilters {
  status?: SaleStatusType;
  customer_id?: number;
  date_from?: string;
  date_to?: string;
  min_total?: number;
  max_total?: number;
}

export interface SaleValidationErrors {
  customer_id?: string[];
  discount_percentage?: string[];
  items?: string[];
  'items.*.product_id'?: string[];
  'items.*.quantity'?: string[];
  status?: string[];
}

export interface SaleStatusOption {
  value: SaleStatusType;
  label: string;
  color: string;
}

export const SALE_STATUS_OPTIONS: SaleStatusOption[] = [
  { value: SaleStatus.PENDING, label: 'Pendente', color: 'yellow' },
  { value: SaleStatus.COMPLETED, label: 'Pago', color: 'green' },
  { value: SaleStatus.CANCELLED, label: 'Cancelado', color: 'red' },
];

export const ALLOWED_STATUS_TRANSITIONS: Record<
  SaleStatusType,
  SaleStatusType[]
> = {
  [SaleStatus.PENDING]: [SaleStatus.COMPLETED, SaleStatus.CANCELLED],
  [SaleStatus.COMPLETED]: [SaleStatus.CANCELLED],
  [SaleStatus.CANCELLED]: [SaleStatus.PENDING],
};

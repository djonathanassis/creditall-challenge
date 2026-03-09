import type {
  Sale,
  SaleItem,
  SaleWithDetails,
  CreateSaleRequest,
  UpdateSaleStatusRequest,
  SalesFilters,
  SaleStatusType,
} from '../types/sale';
import { SaleStatus, ALLOWED_STATUS_TRANSITIONS } from '../types/sale';
import type { PaginatedResponse } from '../types/api';
import {
  apiGet,
  apiGetPaginated,
  apiPost,
  apiPut,
  apiDelete,
  handleApiError,
} from './api';
import { API_ENDPOINTS } from '../utils/constants';

export class SalesService {
  static async getAll(
    filters: SalesFilters = {}
  ): Promise<PaginatedResponse<Sale>> {
    try {
      const processedFilters = { ...filters };

      if (processedFilters.date_from) {
        processedFilters.date_from = this.formatDateForApi(
          processedFilters.date_from
        );
      }

      if (processedFilters.date_to) {
        processedFilters.date_to = this.formatDateForApi(
          processedFilters.date_to
        );
      }

      const response = await apiGetPaginated<Sale>(
        API_ENDPOINTS.SALES.LIST,
        processedFilters as Record<string, unknown>
      );
      return response;
    } catch (error) {
      const apiError = handleApiError(error);
      throw new Error(apiError.message);
    }
  }

  static async getById(id: number): Promise<SaleWithDetails> {
    try {
      const sale = await apiGet<SaleWithDetails>(API_ENDPOINTS.SALES.SHOW(id));
      return sale;
    } catch (error) {
      const apiError = handleApiError(error);

      if (apiError.message.includes('404')) {
        throw new Error('Venda não encontrada');
      }

      throw new Error(apiError.message);
    }
  }

  static async create(data: CreateSaleRequest): Promise<Sale> {
    try {
      const validationErrors = this.validateSaleData(data);

      if (validationErrors.length > 0) {
        throw new Error(validationErrors.join(', '));
      }

      return await apiPost<Sale>(API_ENDPOINTS.SALES.CREATE, data);
    } catch (error) {
      const apiError = handleApiError(error);

      if (apiError.errors) {
        const validationMessages = Object.entries(apiError.errors)
          .map(([field, messages]) => {
            if (field === 'customer_id') {
              return 'Cliente é obrigatório';
            }
            if (field.includes('items')) {
              return 'Pelo menos um item é obrigatório';
            }
            if (field.includes('product_id')) {
              return 'Produto inválido ou não encontrado';
            }
            if (field.includes('quantity')) {
              return 'Quantidade deve ser maior que zero';
            }
            return messages.join(', ');
          })
          .flat();

        throw new Error(validationMessages.join(', '));
      }

      if (apiError.message.includes('estoque')) {
        throw new Error(apiError.message);
      }

      throw new Error(apiError.message);
    }
  }

  static async updateStatus(id: number, status: SaleStatusType): Promise<Sale> {
    try {
      const currentSale = await this.getById(id);

      if (!this.isValidStatusTransition(currentSale.status, status)) {
        throw new Error(
          `Não é possível alterar status de "${currentSale.status}" para "${status}"`
        );
      }

      const data: UpdateSaleStatusRequest = { status };
      return await apiPut<Sale>(API_ENDPOINTS.SALES.UPDATE(id), data);
    } catch (error) {
      const apiError = handleApiError(error);

      if (apiError.message.includes('404')) {
        throw new Error('Venda não encontrada');
      }

      if (apiError.message.includes('409')) {
        throw new Error(
          'Conflito na atualização do status. Verifique o estoque disponível.'
        );
      }

      throw new Error(apiError.message);
    }
  }

  static async delete(id: number): Promise<void> {
    try {
      await apiDelete(API_ENDPOINTS.SALES.DELETE(id));
    } catch (error) {
      const apiError = handleApiError(error);

      if (apiError.message.includes('404')) {
        throw new Error('Venda não encontrada');
      }

      throw new Error(apiError.message);
    }
  }

  static async getItems(saleId: number): Promise<SaleItem[]> {
    try {
      const items = await apiGet<SaleItem[]>(API_ENDPOINTS.SALES.ITEMS(saleId));
      return items;
    } catch (error) {
      const apiError = handleApiError(error);

      if (apiError.message.includes('404')) {
        throw new Error('Venda não encontrada');
      }

      throw new Error(apiError.message);
    }
  }

  static async getByCustomer(
    customerId: number,
    filters: Omit<SalesFilters, 'customer_id'> = {}
  ): Promise<PaginatedResponse<Sale>> {
    return this.getAll({
      ...filters,
      customer_id: customerId,
    });
  }

  static async getByStatus(
    status: SaleStatusType,
    filters: Omit<SalesFilters, 'status'> = {}
  ): Promise<PaginatedResponse<Sale>> {
    return this.getAll({
      ...filters,
      status,
    });
  }

  static async getPendingSales(
    filters: Omit<SalesFilters, 'status'> = {}
  ): Promise<PaginatedResponse<Sale>> {
    return this.getByStatus(SaleStatus.PENDING, filters);
  }

  static async getByDateRange(
    dateFrom: string,
    dateTo: string,
    filters: Omit<SalesFilters, 'date_from' | 'date_to'> = {}
  ): Promise<PaginatedResponse<Sale>> {
    return this.getAll({
      ...filters,
      date_from: dateFrom,
      date_to: dateTo,
    });
  }

  static async getTodaySales(): Promise<PaginatedResponse<Sale>> {
    const today = new Date().toISOString().split('T')[0];

    return this.getByDateRange(today, today, {
      per_page: 100,
    });
  }

  static calculateSaleTotals(
    items: { unitPrice: number; quantity: number }[],
    discountPercentage: number = 0
  ): {
    subtotal: number;
    discountAmount: number;
    total: number;
  } {
    const subtotal = items.reduce(
      (sum, item) => sum + item.unitPrice * item.quantity,
      0
    );
    const discountAmount = (subtotal * discountPercentage) / 100;
    const total = subtotal - discountAmount;

    return {
      subtotal,
      discountAmount,
      total,
    };
  }

  static isValidStatusTransition(
    currentStatus: SaleStatusType,
    newStatus: SaleStatusType
  ): boolean {
    if (currentStatus === newStatus) {
      return true;
    }

    const allowedTransitions = ALLOWED_STATUS_TRANSITIONS[currentStatus];
    return allowedTransitions.includes(newStatus);
  }

  static getAllowedStatusTransitions(
    currentStatus: SaleStatusType
  ): SaleStatusType[] {
    return ALLOWED_STATUS_TRANSITIONS[currentStatus] || [];
  }

  static validateSaleData(data: CreateSaleRequest): string[] {
    const errors: string[] = [];

    if (!data.customer_id || data.customer_id <= 0) {
      errors.push('Cliente é obrigatório');
    }

    if (
      data.discount_percentage !== undefined &&
      data.discount_percentage !== null
    ) {
      if (data.discount_percentage < 0 || data.discount_percentage > 100) {
        errors.push('Desconto deve estar entre 0% e 100%');
      }
    }

    if (!data.items || data.items.length === 0) {
      errors.push('Pelo menos um item é obrigatório');
    } else {
      data.items.forEach((item, index) => {
        if (!item.product_id || item.product_id <= 0) {
          errors.push(`Item ${index + 1}: Produto é obrigatório`);
        }

        if (!item.quantity || item.quantity <= 0) {
          errors.push(`Item ${index + 1}: Quantidade deve ser maior que zero`);
        }
      });
    }

    return errors;
  }

  private static formatDateForApi(date: string): string {
    if (/^\d{4}-\d{2}-\d{2}$/.test(date)) {
      return date;
    }

    const dateObj = new Date(date);

    if (isNaN(dateObj.getTime())) {
      return date;
    }

    return dateObj.toISOString().split('T')[0];
  }

  static async getStatistics(): Promise<{
    totalSales: number;
    pendingSales: number;
    completedSales: number;
    cancelledSales: number;
    totalRevenue: number;
  }> {
    try {
      const allSales = await this.getAll({ per_page: 1000 });

      if (
        !allSales ||
        !allSales.meta ||
        typeof allSales.meta.total !== 'number'
      ) {
        console.warn('Invalid sales response for statistics:', allSales);
        return {
          totalSales: 0,
          pendingSales: 0,
          completedSales: 0,
          cancelledSales: 0,
          totalRevenue: 0,
        };
      }

      const salesData = Array.isArray(allSales.data) ? allSales.data : [];

      const totalSales = allSales.meta.total;
      const pendingSales = salesData.filter(
        (sale) => sale.status === SaleStatus.PENDING
      ).length;
      const completedSales = salesData.filter(
        (sale) => sale.status === SaleStatus.COMPLETED
      ).length;
      const cancelledSales = salesData.filter(
        (sale) => sale.status === SaleStatus.CANCELLED
      ).length;
      const totalRevenue = salesData
        .filter((sale) => sale.status === SaleStatus.COMPLETED)
        .reduce((sum, sale) => sum + parseFloat(sale.total_amount), 0);

      return {
        totalSales,
        pendingSales,
        completedSales,
        cancelledSales,
        totalRevenue,
      };
    } catch (error) {
      console.error('Error loading sales statistics:', error);

      return {
        totalSales: 0,
        pendingSales: 0,
        completedSales: 0,
        cancelledSales: 0,
        totalRevenue: 0,
      };
    }
  }
}

export const {
  getAll: getAllSales,
  getById: getSaleById,
  create: createSale,
  updateStatus: updateSaleStatus,
  delete: deleteSale,
  getItems: getSaleItems,
  getByCustomer: getSalesByCustomer,
  getByStatus: getSalesByStatus,
  getPendingSales,
  getByDateRange: getSalesByDateRange,
  getTodaySales,
  calculateSaleTotals,
  isValidStatusTransition,
  getAllowedStatusTransitions,
  validateSaleData,
  getStatistics: getSalesStatistics,
} = SalesService;

export default SalesService;

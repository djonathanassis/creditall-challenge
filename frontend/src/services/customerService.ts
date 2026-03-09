import type {
  Customer,
  CustomerWithStats,
  CreateCustomerRequest,
  UpdateCustomerRequest,
  CustomerFilters,
} from '../types/customer';
import type { Sale } from '../types/sale';
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
import { validateAndFormatCpf, cleanCpf } from '../utils/cpfUtils';

export class CustomerService {
  static async getAll(
    filters: CustomerFilters = {}
  ): Promise<PaginatedResponse<Customer>> {
    try {
      const response = await apiGetPaginated<Customer>(
        API_ENDPOINTS.CUSTOMERS.LIST,
        filters as Record<string, unknown>
      );
      return response;
    } catch (error) {
      const apiError = handleApiError(error);
      throw new Error(apiError.message);
    }
  }

  static async getById(id: number): Promise<CustomerWithStats> {
    try {
      const customer = await apiGet<CustomerWithStats>(
        API_ENDPOINTS.CUSTOMERS.SHOW(id)
      );
      return customer;
    } catch (error) {
      const apiError = handleApiError(error);

      if (apiError.message.includes('404')) {
        throw new Error('Cliente não encontrado');
      }

      throw new Error(apiError.message);
    }
  }

  static async create(data: CreateCustomerRequest): Promise<Customer> {
    try {
      const cpfValidation = validateAndFormatCpf(data.cpf);

      if (!cpfValidation.isValid) {
        throw new Error(cpfValidation.error || 'CPF inválido');
      }

      const requestData = {
        ...data,
        cpf: cpfValidation.cleaned,
      };

      return await apiPost<Customer>(
        API_ENDPOINTS.CUSTOMERS.CREATE,
        requestData
      );
    } catch (error) {
      const apiError = handleApiError(error);

      if (apiError.errors) {
        const validationMessages = Object.entries(apiError.errors)
          .map(([field, messages]) => {
            if (field === 'cpf') {
              return 'CPF inválido ou já está sendo usado';
            }
            if (field === 'email') {
              return 'Email já está sendo usado';
            }
            return messages.join(', ');
          })
          .flat();

        throw new Error(validationMessages.join(', '));
      }

      throw new Error(apiError.message);
    }
  }

  static async update(
    id: number,
    data: UpdateCustomerRequest
  ): Promise<Customer> {
    try {
      const requestData = { ...data };

      if (data.cpf) {
        const cpfValidation = validateAndFormatCpf(data.cpf);

        if (!cpfValidation.isValid) {
          throw new Error(cpfValidation.error || 'CPF inválido');
        }

        requestData.cpf = cpfValidation.cleaned;
      }

      return await apiPut<Customer>(
        API_ENDPOINTS.CUSTOMERS.UPDATE(id),
        requestData
      );
    } catch (error) {
      const apiError = handleApiError(error);

      if (apiError.message.includes('404')) {
        throw new Error('Cliente não encontrado');
      }

      if (apiError.errors) {
        const validationMessages = Object.entries(apiError.errors)
          .map(([field, messages]) => {
            if (field === 'cpf') {
              return 'CPF inválido ou já está sendo usado';
            }
            if (field === 'email') {
              return 'Email já está sendo usado';
            }
            return messages.join(', ');
          })
          .flat();

        throw new Error(validationMessages.join(', '));
      }

      throw new Error(apiError.message);
    }
  }

  static async delete(id: number): Promise<void> {
    try {
      await apiDelete(API_ENDPOINTS.CUSTOMERS.DELETE(id));
    } catch (error) {
      const apiError = handleApiError(error);

      if (apiError.message.includes('404')) {
        throw new Error('Cliente não encontrado');
      }

      if (apiError.message.includes('409')) {
        throw new Error('Não é possível excluir cliente com vendas associadas');
      }

      throw new Error(apiError.message);
    }
  }

  static async getSales(
    customerId: number,
    filters: Omit<CustomerFilters, 'customer_id'> = {}
  ): Promise<PaginatedResponse<Sale>> {
    try {
      const response = await apiGet<PaginatedResponse<Sale>>(
        API_ENDPOINTS.CUSTOMERS.SALES(customerId),
        filters as Record<string, unknown>
      );
      return response;
    } catch (error) {
      const apiError = handleApiError(error);

      if (apiError.message.includes('404')) {
        throw new Error('Cliente não encontrado');
      }

      throw new Error(apiError.message);
    }
  }

  static async search(
    query: string,
    filters: Omit<CustomerFilters, 'search'> = {}
  ): Promise<PaginatedResponse<Customer>> {
    return this.getAll({
      ...filters,
      search: query,
    });
  }

  static async findByCpf(cpf: string): Promise<Customer | null> {
    const cleanedCpf = cleanCpf(cpf);

    if (cleanedCpf.length !== 11) {
      throw new Error('CPF deve conter 11 dígitos');
    }

    const response = await this.search(cleanedCpf);
    const customer = response.data.find((c) => cleanCpf(c.cpf) === cleanedCpf);

    return customer || null;
  }

  static async getRecentCustomers(): Promise<Customer[]> {
    const response = await this.getAll({ per_page: 100 });

    return response.data
      .sort(
        (a, b) =>
          new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
      )
      .slice(0, 10);
  }

  static validateCustomerData(
    data: CreateCustomerRequest | UpdateCustomerRequest
  ): string[] {
    const errors: string[] = [];

    if ('name' in data && data.name !== undefined) {
      if (!data.name || data.name.trim().length === 0) {
        errors.push('Nome é obrigatório');
      } else if (data.name.length > 255) {
        errors.push('Nome não pode ter mais que 255 caracteres');
      }
    }

    if ('email' in data && data.email !== undefined) {
      const emailRegex = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;

      if (!data.email || data.email.trim().length === 0) {
        errors.push('Email é obrigatório');
      } else if (!emailRegex.test(data.email)) {
        errors.push('Email deve ter um formato válido');
      } else if (data.email.length > 255) {
        errors.push('Email não pode ter mais que 255 caracteres');
      }
    }

    if ('cpf' in data && data.cpf !== undefined) {
      const cpfValidation = validateAndFormatCpf(data.cpf);

      if (!cpfValidation.isValid) {
        errors.push(cpfValidation.error || 'CPF inválido');
      }
    }

    if ('phone' in data && data.phone !== undefined) {
      if (!data.phone || data.phone.trim().length === 0) {
        errors.push('Telefone é obrigatório');
      }
    }

    return errors;
  }

  static formatCustomerForDisplay(customer: Customer): Customer {
    return {
      ...customer,
      phone: customer.phone,
    };
  }
}

export const {
  getAll: getAllCustomers,
  getById: getCustomerById,
  create: createCustomer,
  update: updateCustomer,
  delete: deleteCustomer,
  getSales: getCustomerSales,
  search: searchCustomers,
  findByCpf,
  getRecentCustomers,
  validateCustomerData,
  formatCustomerForDisplay,
} = CustomerService;

export default CustomerService;

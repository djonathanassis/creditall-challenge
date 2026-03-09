import axios, { AxiosError } from 'axios';
import type { AxiosInstance, AxiosRequestConfig, AxiosResponse } from 'axios';
import type { ApiResponse, ApiError, PaginatedResponse } from '../types/api';
import { HTTP_STATUS } from '../types/api';
import { API_CONFIG, STORAGE_KEYS, ERROR_MESSAGES } from '../utils/constants';

const api: AxiosInstance = axios.create({
  baseURL: API_CONFIG.BASE_URL,
  timeout: API_CONFIG.TIMEOUT,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

export const TokenManager = {
  get(): string | null {
    return localStorage.getItem(STORAGE_KEYS.AUTH_TOKEN);
  },

  set(token: string): void {
    localStorage.setItem(STORAGE_KEYS.AUTH_TOKEN, token);
  },

  clear(): void {
    localStorage.removeItem(STORAGE_KEYS.AUTH_TOKEN);
    localStorage.removeItem(STORAGE_KEYS.USER_DATA);
  },

  isValid(): boolean {
    const token = this.get();
    return !!token && token.length > 0;
  },
};

api.interceptors.request.use(
  (config) => {
    const token = TokenManager.get();

    if (token) {
      config.headers = config.headers || {};
      config.headers.Authorization = `Bearer ${token}`;
    }

    if (import.meta.env.DEV) {
      console.log('🚀 API Request:', {
        method: config.method?.toUpperCase(),
        url: config.url,
        data: config.data,
      });
    }

    return config;
  },
  (error) => {
    console.error('❌ Request Interceptor Error:', error);
    return Promise.reject(error);
  }
);

api.interceptors.response.use(
  (response: AxiosResponse): AxiosResponse => {
    if (import.meta.env.DEV) {
      console.log('✅ API Response:', {
        status: response.status,
        url: response.config.url,
        data: response.data,
      });
    }

    return response;
  },
  (error: AxiosError): Promise<never> => {
    if (import.meta.env.DEV) {
      console.error('❌ API Error:', {
        status: error.response?.status,
        url: error.config?.url,
        message: error.message,
        data: error.response?.data,
      });
    }

    if (error.response) {
      const { status } = error.response;

      switch (status) {
        case HTTP_STATUS.UNAUTHORIZED:
          TokenManager.clear();
          if (typeof window !== 'undefined') {
            window.location.href = '/login';
          }
          break;

        case HTTP_STATUS.FORBIDDEN:
          break;

        case HTTP_STATUS.NOT_FOUND:
          break;

        case HTTP_STATUS.CONFLICT:
          break;

        case HTTP_STATUS.UNPROCESSABLE_ENTITY:
          break;

        case HTTP_STATUS.INTERNAL_SERVER_ERROR:
          break;
      }
    }

    return Promise.reject(error);
  }
);

export function handleApiError(error: unknown): ApiError {
  if (axios.isAxiosError(error)) {
    const axiosError = error as AxiosError<ApiError>;

    if (axiosError.response?.data) {
      return axiosError.response.data;
    }

    if (axiosError.code === 'ECONNABORTED') {
      return {
        success: false,
        message: ERROR_MESSAGES.TIMEOUT_ERROR,
      };
    }

    if (axiosError.message === 'Network Error') {
      return {
        success: false,
        message: ERROR_MESSAGES.NETWORK_ERROR,
      };
    }

    return {
      success: false,
      message: axiosError.message || ERROR_MESSAGES.UNKNOWN_ERROR,
    };
  }

  return {
    success: false,
    message: ERROR_MESSAGES.UNKNOWN_ERROR,
  };
}

export function createFormData(data: Record<string, unknown>): FormData {
  const formData = new FormData();

  Object.keys(data).forEach((key) => {
    const value = data[key];

    if (value !== null && value !== undefined) {
      if (value instanceof File) {
        formData.append(key, value);
      } else if (typeof value === 'object') {
        formData.append(key, JSON.stringify(value));
      } else {
        formData.append(key, String(value));
      }
    }
  });

  return formData;
}

export async function apiMultipart<T>(
  url: string,
  data: Record<string, unknown>,
  config?: AxiosRequestConfig
): Promise<T>;
export async function apiMultipart<T>(
  url: string,
  data: Record<string, unknown>,
  method: 'POST' | 'PUT' | 'PATCH',
  config?: AxiosRequestConfig
): Promise<T>;
export async function apiMultipart<T>(
  url: string,
  data: Record<string, unknown>,
  methodOrConfig?: 'POST' | 'PUT' | 'PATCH' | AxiosRequestConfig,
  config?: AxiosRequestConfig
): Promise<T> {
  const formData = createFormData(data);

  let method: 'POST' | 'PUT' | 'PATCH' = 'POST';
  let finalConfig: AxiosRequestConfig | undefined = config;

  if (typeof methodOrConfig === 'string') {
    method = methodOrConfig;
  } else if (methodOrConfig && typeof methodOrConfig === 'object') {
    finalConfig = methodOrConfig;
  }

  if (method === 'PUT' || method === 'PATCH') {
    formData.append('_method', method.toUpperCase());

    const response = await api.post<ApiResponse<T>>(url, formData, {
      ...finalConfig,
      headers: {
        ...finalConfig?.headers,
        'Content-Type': 'multipart/form-data',
      },
    });

    return response.data.data;
  }

  const response = await api.post<ApiResponse<T>>(url, formData, {
    ...finalConfig,
    headers: {
      ...finalConfig?.headers,
      'Content-Type': 'multipart/form-data',
    },
  });

  return response.data.data;
}

export async function apiGet<T>(
  url: string,
  params?: Record<string, unknown>,
  config?: AxiosRequestConfig
): Promise<T> {
  const response = await api.get<ApiResponse<T>>(url, {
    ...config,
    params,
  });

  return response.data.data;
}

export async function apiGetPaginated<T>(
  url: string,
  params?: Record<string, unknown>,
  config?: AxiosRequestConfig
): Promise<PaginatedResponse<T>> {
  const response = await api.get<PaginatedResponse<T>>(url, {
    ...config,
    params,
  });

  return response.data;
}

export async function apiPost<T>(
  url: string,
  data?: unknown,
  config?: AxiosRequestConfig
): Promise<T> {
  const response = await api.post<ApiResponse<T>>(url, data, config);
  return response.data.data;
}

export async function apiPut<T>(
  url: string,
  data?: unknown,
  config?: AxiosRequestConfig
): Promise<T> {
  const response = await api.put<ApiResponse<T>>(url, data, config);
  return response.data.data;
}

export async function apiDelete<T>(
  url: string,
  config?: AxiosRequestConfig
): Promise<T> {
  const response = await api.delete<ApiResponse<T>>(url, config);
  return response.data.data;
}

export async function checkApiHealth(): Promise<boolean> {
  try {
    const response = await api.get('/health');
    return response.status === HTTP_STATUS.OK;
  } catch (error) {
    console.error('API Health Check Failed:', error);
    return false;
  }
}

export default api;

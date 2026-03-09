export const API_CONFIG = {
  BASE_URL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
  TIMEOUT: parseInt(import.meta.env.VITE_API_TIMEOUT) || 10000,
  RETRY_ATTEMPTS: 3,
  RETRY_DELAY: 1000,
} as const;

export const API_ENDPOINTS = {
  AUTH: {
    LOGIN: '/auth/login',
    LOGOUT: '/auth/logout',
    ME: '/auth/me',
  },

  PRODUCTS: {
    LIST: '/products',
    SHOW: (id: number) => `/products/${id}`,
    CREATE: '/products',
    UPDATE: (id: number) => `/products/${id}`,
    DELETE: (id: number) => `/products/${id}`,
  },

  CUSTOMERS: {
    LIST: '/customers',
    SHOW: (id: number) => `/customers/${id}`,
    CREATE: '/customers',
    UPDATE: (id: number) => `/customers/${id}`,
    DELETE: (id: number) => `/customers/${id}`,
    SALES: (id: number) => `/customers/${id}/sales`,
  },

  SALES: {
    LIST: '/sales',
    SHOW: (id: number) => `/sales/${id}`,
    CREATE: '/sales',
    UPDATE: (id: number) => `/sales/${id}`,
    DELETE: (id: number) => `/sales/${id}`,
    ITEMS: (id: number) => `/sales/${id}/items`,
  },

  HEALTH: '/health',
} as const;

export const STORAGE_KEYS = {
  AUTH_TOKEN: 'auth_token',
  USER_DATA: 'user_data',
  THEME_PREFERENCE: 'theme_preference',
  LANGUAGE_PREFERENCE: 'language_preference',
} as const;

export const APP_CONFIG = {
  NAME: import.meta.env.VITE_APP_NAME || 'Sistema de Gestão de Vendas',
  VERSION: '1.0.0',
  PAGINATION: {
    DEFAULT_PER_PAGE: 15,
    MAX_PER_PAGE: 100,
  },
  FILE_UPLOAD: {
    MAX_SIZE: 2048 * 1024,
    ALLOWED_TYPES: ['image/jpeg', 'image/png', 'image/gif'],
    ALLOWED_EXTENSIONS: ['.jpg', '.jpeg', '.png', '.gif'],
  },
} as const;

export const VALIDATION_PATTERNS = {
  EMAIL: /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/,
  CPF: /^\d{3}\.\d{3}\.\d{3}-\d{2}$/,
  PHONE: /^\(\d{2}\)\s\d{4,5}-\d{4}$/,
  PASSWORD: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/,
} as const;

export const ERROR_MESSAGES = {
  NETWORK_ERROR: 'Erro de conexão. Verifique sua internet e tente novamente.',
  UNAUTHORIZED: 'Sessão expirada. Faça login novamente.',
  FORBIDDEN: 'Você não tem permissão para realizar esta ação.',
  NOT_FOUND: 'Recurso não encontrado.',
  VALIDATION_ERROR: 'Erro na validação dos dados fornecidos.',
  SERVER_ERROR: 'Erro interno do servidor. Tente novamente mais tarde.',
  TIMEOUT_ERROR: 'Tempo limite excedido. Tente novamente.',
  UNKNOWN_ERROR: 'Ocorreu um erro inesperado.',
} as const;

export const SUCCESS_MESSAGES = {
  LOGIN: 'Login realizado com sucesso!',
  LOGOUT: 'Logout realizado com sucesso!',
  CREATE: 'Registro criado com sucesso!',
  UPDATE: 'Registro atualizado com sucesso!',
  DELETE: 'Registro excluído com sucesso!',
  SAVE: 'Dados salvos com sucesso!',
} as const;

export const SALE_STATUS_CONFIG = {
  pending: {
    label: 'Pendente',
    color: 'yellow',
    bgColor: 'bg-yellow-100',
    textColor: 'text-yellow-800',
    badgeColor: 'bg-yellow-500',
  },
  completed: {
    label: 'Pago',
    color: 'green',
    bgColor: 'bg-green-100',
    textColor: 'text-green-800',
    badgeColor: 'bg-green-500',
  },
  cancelled: {
    label: 'Cancelado',
    color: 'red',
    bgColor: 'bg-red-100',
    textColor: 'text-red-800',
    badgeColor: 'bg-red-500',
  },
} as const;

export const THEME_CONFIG = {
  PRIMARY_COLOR: '#3B82F6', // Blue
  SECONDARY_COLOR: '#10B981', // Green
  DANGER_COLOR: '#EF4444', // Red
  WARNING_COLOR: '#F59E0B', // Yellow
  INFO_COLOR: '#6366F1', // Indigo
} as const;

export function cleanCpf(cpf: string): string {
  return cpf.replace(/[^\d]/g, '');
}

export function formatCpf(cpf: string): string {
  const cleaned = cleanCpf(cpf);

  if (cleaned.length !== 11) {
    return cpf; // Return original if invalid length
  }

  return cleaned.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}

export function validateCpf(cpf: string): boolean {
  const cleaned = cleanCpf(cpf);

  if (cleaned.length !== 11) {
    return false;
  }

  if (/^(\d)\1{10}$/.test(cleaned)) {
    return false;
  }

  let sum = 0;
  for (let i = 0; i < 9; i++) {
    sum += parseInt(cleaned[i]) * (10 - i);
  }

  let remainder = sum % 11;
  const firstDigit = remainder < 2 ? 0 : 11 - remainder;

  if (parseInt(cleaned[9]) !== firstDigit) {
    return false;
  }

  sum = 0;
  for (let i = 0; i < 10; i++) {
    sum += parseInt(cleaned[i]) * (11 - i);
  }

  remainder = sum % 11;
  const secondDigit = remainder < 2 ? 0 : 11 - remainder;

  return parseInt(cleaned[10]) === secondDigit;
}

export function validateAndFormatCpf(cpf: string): {
  isValid: boolean;
  formatted: string;
  cleaned: string;
  error?: string;
} {
  const cleaned = cleanCpf(cpf);

  if (cleaned.length === 0) {
    return {
      isValid: false,
      formatted: '',
      cleaned: '',
      error: 'CPF é obrigatório',
    };
  }

  if (cleaned.length !== 11) {
    return {
      isValid: false,
      formatted: cpf,
      cleaned,
      error: 'CPF deve conter 11 dígitos',
    };
  }

  const isValid = validateCpf(cleaned);
  const formatted = formatCpf(cleaned);

  return {
    isValid,
    formatted,
    cleaned,
    error: isValid ? undefined : 'CPF inválido',
  };
}

export function maskCpfInput(value: string): string {
  const cleaned = cleanCpf(value);

  if (cleaned.length <= 3) {
    return cleaned;
  } else if (cleaned.length <= 6) {
    return cleaned.replace(/(\d{3})(\d{0,3})/, '$1.$2');
  } else if (cleaned.length <= 9) {
    return cleaned.replace(/(\d{3})(\d{3})(\d{0,3})/, '$1.$2.$3');
  } else {
    return cleaned.replace(/(\d{3})(\d{3})(\d{3})(\d{0,2})/, '$1.$2.$3-$4');
  }
}

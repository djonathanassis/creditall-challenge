import { useState, useEffect, useCallback } from 'react';
import type { FormEvent, ChangeEvent } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import type {
  Customer,
  CreateCustomerRequest,
  UpdateCustomerRequest,
} from '../types/customer';
import CustomerService from '../services/customerService';
import { LoadingSpinner } from '../components/Loading';
import { notificationManager } from '../utils/notificationManager';
import { validateAndFormatCpf, maskCpfInput } from '../utils/cpfUtils';
import { formatPhone, cleanPhone } from '../utils/formatters';

interface CustomerFormData {
  name: string;
  email: string;
  cpf: string;
  phone: string;
}

export default function CustomerFormPage() {
  const navigate = useNavigate();
  const { id } = useParams<{ id: string }>();
  const isEditing = !!id;

  const [customer, setCustomer] = useState<Customer | null>(null);
  const [isLoading, setIsLoading] = useState(isEditing);
  const [isSaving, setIsSaving] = useState(false);

  const [formData, setFormData] = useState<CustomerFormData>({
    name: '',
    email: '',
    cpf: '',
    phone: '',
  });

  const [errors, setErrors] = useState<Record<string, string>>({});
  const [cpfStatus, setCpfStatus] = useState<{
    isValid: boolean;
    message?: string;
  }>({ isValid: false });

  const loadCustomer = useCallback(
    async (customerId: number) => {
      try {
        setIsLoading(true);
        const customerData = await CustomerService.getById(customerId);
        setCustomer(customerData);

        setFormData({
          name: customerData.name,
          email: customerData.email,
          cpf: customerData.cpf,
          phone: customerData.phone,
        });

        const cpfValidation = validateAndFormatCpf(customerData.cpf);
        setCpfStatus({
          isValid: cpfValidation.isValid,
          message: cpfValidation.error,
        });
      } catch (error) {
        const message =
          error instanceof Error ? error.message : 'Erro ao carregar cliente';
        notificationManager.error('Erro ao carregar cliente', message);
        navigate('/customers');
      } finally {
        setIsLoading(false);
      }
    },
    [navigate]
  );

  useEffect(() => {
    if (isEditing && id) {
      loadCustomer(Number(id));
    }
  }, [id, isEditing, loadCustomer]);

  const handleInputChange = (e: ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;

    let processedValue = value;

    if (name === 'cpf') {
      processedValue = maskCpfInput(value);

      const cpfValidation = validateAndFormatCpf(processedValue);
      setCpfStatus({
        isValid: cpfValidation.isValid,
        message: cpfValidation.error,
      });
    }

    if (name === 'phone') {
      const cleaned = cleanPhone(value);
      if (cleaned.length <= 11) {
        processedValue = formatPhone(cleaned);
      } else {
        return;
      }
    }

    setFormData((prev) => ({ ...prev, [name]: processedValue }));

    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: '' }));
    }
  };

  const validateForm = (): boolean => {
    const newErrors: Record<string, string> = {};

    if (!formData.name.trim()) {
      newErrors.name = 'Nome é obrigatório';
    }

    const emailRegex = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;
    if (!formData.email.trim()) {
      newErrors.email = 'Email é obrigatório';
    } else if (!emailRegex.test(formData.email)) {
      newErrors.email = 'Email deve ter um formato válido';
    }

    if (!formData.cpf.trim()) {
      newErrors.cpf = 'CPF é obrigatório';
    } else if (!cpfStatus.isValid) {
      newErrors.cpf = cpfStatus.message || 'CPF inválido';
    }

    if (!formData.phone.trim()) {
      newErrors.phone = 'Telefone é obrigatório';
    } else {
      const phoneClean = cleanPhone(formData.phone);
      if (phoneClean.length < 10 || phoneClean.length > 11) {
        newErrors.phone = 'Telefone deve ter 10 ou 11 dígitos';
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
      const customerData: CreateCustomerRequest | UpdateCustomerRequest = {
        name: formData.name.trim(),
        email: formData.email.trim().toLowerCase(),
        cpf: formData.cpf.trim(),
        phone: formData.phone.trim(),
      };

      if (isEditing && customer) {
        await CustomerService.update(
          customer.id,
          customerData as UpdateCustomerRequest
        );
        notificationManager.success(
          'Cliente atualizado',
          'Cliente foi atualizado com sucesso'
        );
      } else {
        await CustomerService.create(customerData as CreateCustomerRequest);
        notificationManager.success(
          'Cliente criado',
          'Cliente foi criado com sucesso'
        );
      }

      navigate('/customers');
    } catch (error) {
      const message =
        error instanceof Error ? error.message : 'Erro ao salvar cliente';
      notificationManager.error('Erro ao salvar cliente', message);
    } finally {
      setIsSaving(false);
    }
  };

  if (isLoading) {
    return (
      <div className='flex items-center justify-center h-64'>
        <LoadingSpinner size='lg' text='Carregando cliente...' />
      </div>
    );
  }

  return (
    <div className='space-y-6'>
      <div className='md:flex md:items-center md:justify-between'>
        <div className='flex-1 min-w-0'>
          <h1 className='text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate'>
            {isEditing ? 'Editar Cliente' : 'Novo Cliente'}
          </h1>
          <p className='mt-1 text-sm text-gray-500'>
            {isEditing
              ? 'Atualize as informações do cliente'
              : 'Cadastre um novo cliente no sistema'}
          </p>
        </div>
        <div className='mt-4 flex md:mt-0 md:ml-4'>
          <button
            type='button'
            onClick={() => navigate('/customers')}
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
            <div className='sm:col-span-2'>
              <label htmlFor='name' className='form-label'>
                Nome Completo *
              </label>
              <input
                type='text'
                name='name'
                id='name'
                className={`form-input ${errors.name ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : ''}`}
                placeholder='Digite o nome completo do cliente'
                value={formData.name}
                onChange={handleInputChange}
                disabled={isSaving}
              />
              {errors.name && <p className='form-error'>{errors.name}</p>}
            </div>

            {/* Email */}
            <div>
              <label htmlFor='email' className='form-label'>
                Email *
              </label>
              <input
                type='email'
                name='email'
                id='email'
                className={`form-input ${errors.email ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : ''}`}
                placeholder='cliente@exemplo.com'
                value={formData.email}
                onChange={handleInputChange}
                disabled={isSaving}
              />
              {errors.email && <p className='form-error'>{errors.email}</p>}
            </div>

            {/* CPF */}
            <div>
              <label htmlFor='cpf' className='form-label'>
                CPF *
              </label>
              <input
                type='text'
                name='cpf'
                id='cpf'
                className={`form-input font-mono ${
                  errors.cpf
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : cpfStatus.isValid && formData.cpf.length > 0
                      ? 'border-green-300 focus:border-green-500 focus:ring-green-500'
                      : ''
                }`}
                placeholder='000.000.000-00'
                maxLength={14}
                value={formData.cpf}
                onChange={handleInputChange}
                disabled={isSaving}
              />
              {errors.cpf && <p className='form-error'>{errors.cpf}</p>}
              {!errors.cpf && formData.cpf.length > 0 && cpfStatus.isValid && (
                <p className='mt-1 text-sm text-green-600'>✅ CPF válido</p>
              )}
              {!errors.cpf &&
                formData.cpf.length > 0 &&
                !cpfStatus.isValid &&
                cpfStatus.message && (
                  <p className='mt-1 text-sm text-yellow-600'>
                    ⚠️ {cpfStatus.message}
                  </p>
                )}
            </div>

            {/* Phone */}
            <div className='sm:col-span-2'>
              <label htmlFor='phone' className='form-label'>
                Telefone *
              </label>
              <input
                type='tel'
                name='phone'
                id='phone'
                className={`form-input ${errors.phone ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : ''}`}
                placeholder='(11) 99999-9999'
                value={formData.phone}
                onChange={handleInputChange}
                disabled={isSaving}
              />
              {errors.phone && <p className='form-error'>{errors.phone}</p>}
              <p className='mt-1 text-sm text-gray-500'>
                Formato: (11) 99999-9999 para celular ou (11) 9999-9999 para
                fixo
              </p>
            </div>
          </div>

          {/* Form Actions */}
          <div className='flex justify-end space-x-3'>
            <button
              type='button'
              onClick={() => navigate('/customers')}
              className='btn-secondary'
              disabled={isSaving}
            >
              Cancelar
            </button>
            <button
              type='submit'
              className='btn-primary'
              disabled={isSaving || !cpfStatus.isValid}
            >
              {isSaving ? (
                <>
                  <LoadingSpinner size='sm' />
                  <span className='ml-2'>
                    {isEditing ? 'Atualizando...' : 'Salvando...'}
                  </span>
                </>
              ) : (
                <span>
                  {isEditing ? 'Atualizar Cliente' : 'Salvar Cliente'}
                </span>
              )}
            </button>
          </div>
        </form>
      </div>

      {/* Help Section */}
      <div className='card bg-blue-50 border-blue-200'>
        <div className='flex'>
          <div className='flex-shrink-0'>
            <div className='text-xl'>💡</div>
          </div>
          <div className='ml-3'>
            <h3 className='text-sm font-medium text-blue-800'>
              Dicas de preenchimento
            </h3>
            <div className='mt-2 text-sm text-blue-700'>
              <ul className='list-disc list-inside space-y-1'>
                <li>O CPF é validado automaticamente conforme você digita</li>
                <li>
                  O telefone é formatado automaticamente (celular ou fixo)
                </li>
                <li>Todos os campos marcados com * são obrigatórios</li>
                <li>O email deve ser único no sistema</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

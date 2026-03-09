<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DataTransferObjects\Customer\CreateCustomerDTO;
use App\DataTransferObjects\Customer\UpdateCustomerDTO;
use App\Exceptions\CustomerException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\SaleResource;
use App\Models\Customer;
use App\Services\CustomerQueryService;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 👥 Clientes
 *
 * Endpoints para gerenciamento completo de clientes, incluindo CRUD, validação de CPF e estatísticas de vendas.
 * Todos os endpoints são protegidos por autenticação.
 */
class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService,
        private readonly CustomerQueryService $customerQueryService
    ) {}

    /**
     * Listar clientes
     *
     * Retorna uma lista paginada de clientes com opções de busca e filtro.
     * Suporta busca por nome, email e CPF.
     *
     * @authenticated
     *
     * @queryParam search string Filtro de busca por nome, email ou CPF. Example: João Silva
     * @queryParam per_page integer Número de itens por página (padrão: 15, máximo: 100). Example: 20
     * @queryParam page integer Página a ser exibida (padrão: 1). Example: 1
     *
     * @response 200 scenario="Lista de clientes" {
     *   "Success": true,
     *   "message": "Dados recuperados com sucesso",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "João Silva",
     *       "email": "joao@exemplo.com",
     *       "cpf": "123.456.789-00",
     *       "phone": "(11) 99999-9999",
     *       "created_at": "2026-03-06T10:30:00.000000Z",
     *       "updated_at": "2026-03-06T10:30:00.000000Z"
     *     },
     *     {
     *       "id": 2,
     *       "name": "Maria Santos",
     *       "email": "maria@exemplo.com",
     *       "cpf": "987.654.321-00",
     *       "phone": "(11) 88888-8888",
     *       "created_at": "2026-03-06T11:00:00.000000Z",
     *       "updated_at": "2026-03-06T11:00:00.000000Z"
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "per_page": 15,
     *     "total": 25,
     *     "last_page": 2,
     *     "from": 1,
     *     "to": 15
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $customers = $this->customerQueryService->getPaginatedCustomers($request);
        $transformedCustomers = clone $customers;
        $transformedCustomers->getCollection()->transform(function ($customer) {
            return new CustomerResource($customer);
        });

        return $this->paginatedResponse($transformedCustomers);
    }

    /**
     * Criar cliente
     *
     * Cria um cliente no sistema com validação completa incluindo validação de CPF.
     * O CPF deve ser válido e único no sistema.
     *
     * @authenticated
     *
     * @bodyParam name string required Nome completo do cliente. Example: João Silva Santos
     * @bodyParam email string required Email do cliente. Deve ser válido e único. Example: joao.santos@exemplo.com
     * @bodyParam cpf string required CPF do cliente. Deve ser um CPF válido e único. Example: 12345678900
     * @bodyParam phone string required Telefone do cliente com DDD. Example: 11999999999
     *
     * @response 201 scenario="Cliente criado com sucesso" {
     *   "Success": true,
     *   "message": "Cliente criado com sucesso",
     *   "data": {
     *     "id": 1,
     *     "name": "João Silva Santos",
     *     "email": "joao.santos@exemplo.com",
     *     "cpf": "123.456.789-00",
     *     "phone": "(11) 99999-9999",
     *     "created_at": "2026-03-06T10:30:00.000000Z",
     *     "updated_at": "2026-03-06T10:30:00.000000Z"
     *   }
     * }
     * @response 422 scenario="Erro de validação - CPF inválido" {
     *   "success": false,
     *   "message": "Erro na validação dos dados",
     *   "errors": {
     *     "cpf": ["O CPF informado é inválido."]
     *   }
     * }
     * @response 422 scenario="Erro de validação - Email já existe" {
     *   "success": false,
     *   "message": "Erro na validação dos dados",
     *   "errors": {
     *     "email": ["O email informado já está sendo usado."],
     *     "cpf": ["O CPF informado já está sendo usado."]
     *   }
     * }
     * @response 422 scenario="Erro de validação - Campos obrigatórios" {
     *   "success": false,
     *   "message": "Erro na validação dos dados",
     *   "errors": {
     *     "name": ["O campo nome é obrigatório."],
     *     "email": ["O campo email é obrigatório."],
     *     "cpf": ["O campo CPF é obrigatório."],
     *     "phone": ["O campo telefone é obrigatório."]
     *   }
     * }
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        try {
            $dto = CreateCustomerDTO::fromArray($request->validated());
            $customer = $this->customerService->createCustomer($dto);

            return $this->createdResponse(
                new CustomerResource($customer),
                'api.success.customer_created'
            );
        } catch (CustomerException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable $e) {
            return $this->serverErrorResponse('api.errors.server_error');
        }
    }

    /**
     * Exibir cliente
     *
     * Retorna os detalhes completos de um cliente específico, incluindo estatísticas de vendas.
     * As estatísticas incluem total de compras, valor total gasto e número de compras.
     *
     * @authenticated
     *
     * @urlParam id integer required ID do cliente. Example: 1
     *
     * @response 200 scenario="Cliente encontrado com estatísticas" {
     *   "Success": true,
     *   "message": "Dados recuperados com sucesso",
     *   "data": {
     *     "id": 1,
     *     "name": "João Silva Santos",
     *     "email": "joao.santos@exemplo.com",
     *     "cpf": "123.456.789-00",
     *     "phone": "(11) 99999-9999",
     *     "created_at": "2026-03-06T10:30:00.000000Z",
     *     "updated_at": "2026-03-06T10:30:00.000000Z",
     *     "sales_stats": {
     *       "total_sales": 5,
     *       "total_amount": "12500.50",
     *       "average_order_value": "2500.10",
     *       "last_purchase": "2026-03-05T15:30:00.000000Z"
     *     }
     *   }
     * }
     * @response 404 scenario="Cliente não encontrado" {
     *   "success": false,
     *   "message": "Cliente não encontrado"
     * }
     */
    public function show(Customer $customer): JsonResponse
    {
        try {
            $customerWithStats = $this->customerService->getCustomerWithStats($customer->id);

            return $this->successResponse(new CustomerResource($customerWithStats));
        } catch (\Throwable $e) {
            return $this->serverErrorResponse('api.errors.server_error');
        }
    }

    /**
     * Atualizar cliente
     *
     * Atualiza os dados de um cliente existente.
     * Permite atualização parcial dos campos com validação completa incluindo CPF.
     *
     * @authenticated
     *
     * @urlParam id integer required ID do cliente. Example: 1
     *
     * @bodyParam name string, Nome completo do cliente. Example: João Silva Santos Junior
     * @bodyParam email string, Email do cliente. Deve ser válido e único (exceto para o próprio cliente). Example: joao.junior@exemplo.com
     * @bodyParam cpf string CPF do cliente. Deve ser um CPF válido e único (exceto para o próprio cliente). Example: 12345678901
     * @bodyParam phone string Telefone do cliente com DDD. Example: 11888888888
     *
     * @response 200 scenario="Cliente atualizado" {
     *   "Success": true,
     *   "message": "Cliente atualizado com sucesso",
     *   "data": {
     *     "id": 1,
     *     "name": "João Silva Santos Junior",
     *     "email": "joao.junior@exemplo.com",
     *     "cpf": "123.456.789-01",
     *     "phone": "(11) 88888-8888",
     *     "created_at": "2026-03-06T10:30:00.000000Z",
     *     "updated_at": "2026-03-06T12:30:00.000000Z"
     *   }
     * }
     * @response 404 scenario="Cliente não encontrado" {
     *   "success": false,
     *   "message": "Cliente não encontrado"
     * }
     * @response 422 scenario="Erro de validação" {
     *   "success": false,
     *   "message": "Erro na validação dos dados",
     *   "errors": {
     *     "email": ["O email informado já está sendo usado."],
     *     "cpf": ["O CPF informado é inválido."]
     *   }
     * }
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        try {
            $dto = UpdateCustomerDTO::fromArray($request->validated());
            $updatedCustomer = $this->customerService->updateCustomer($customer, $dto);

            return $this->successWithDataResponse(
                new CustomerResource($updatedCustomer),
                'api.success.customer_updated'
            );
        } catch (CustomerException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable $e) {
            return $this->serverErrorResponse('api.errors.server_error');
        }
    }

    /**
     * Excluir cliente
     *
     * Remove um cliente do sistema.
     * Não é possível excluir clientes que possuem vendas associadas (regra de negócio).
     * Esta regra protege a integridade referencial dos dados de vendas.
     *
     * @authenticated
     *
     * @urlParam id integer required ID do cliente. Example: 1
     *
     * @response 200 scenario="Cliente excluído" {
     *   "Success": true,
     *   "message": "Cliente excluído com sucesso",
     *   "data": null
     * }
     * @response 404 scenario="Cliente não encontrado" {
     *   "success": false,
     *   "message": "Cliente não encontrado"
     * }
     * @response 409 scenario="Cliente possui vendas" {
     *   "success": false,
     *   "message": "Não é possível excluir o cliente, pois ele possui vendas associadas"
     * }
     */
    public function destroy(Customer $customer): JsonResponse
    {
        try {
            $deleted = $this->customerService->deleteCustomer($customer);

            if ($deleted) {
                return $this->successResponse(null, 'api.success.customer_deleted');
            }

            return $this->errorResponse('api.errors.customer_delete_failed');
        } catch (CustomerException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable $e) {
            return $this->serverErrorResponse('api.errors.server_error');
        }
    }

    /**
     * Listar vendas de um cliente
     *
     * Retorna todas as vendas realizadas por um cliente específico.
     * Útil para histórico de compras e análise de comportamento do cliente.
     *
     * @authenticated
     *
     * @urlParam customer integer required ID do cliente. Example: 1
     *
     * @queryParam status string Filtrar por status das vendas. Valores: pending, completed, cancelled. Example: completed
     * @queryParam per_page integer Número de itens por página (padrão: 15, máximo: 100). Example: 10
     * @queryParam page integer Página a ser exibida (padrão: 1). Example: 1
     *
     * @response 200 scenario="Histórico de vendas do cliente" {
     *   "success": true,
     *   "message": "Dados recuperados com sucesso",
     *   "data": [
     *     {
     *       "id": 1,
     *       "status": "completed",
     *       "subtotal": "2499.99",
     *       "discount_percentage": "10.00",
     *       "discount_amount": "249.99",
     *       "total": "2250.00",
     *       "items_count": 2,
     *       "created_at": "2026-03-06T10:30:00.000000Z",
     *       "updated_at": "2026-03-06T11:00:00.000000Z",
     *       "items": [
     *         {
     *           "id": 1,
     *           "product": {
     *             "id": 1,
     *             "name": "Notebook Dell Inspiron",
     *             "price": "2299.99"
     *           },
     *           "quantity": 1,
     *           "unit_price": "2299.99",
     *           "subtotal": "2299.99"
     *         }
     *       ]
     *     },
     *     {
     *       "id": 2,
     *       "status": "pending",
     *       "subtotal": "399.90",
     *       "discount_percentage": "5.00",
     *       "discount_amount": "19.99",
     *       "total": "379.91",
     *       "items_count": 1,
     *       "created_at": "2026-03-05T14:20:00.000000Z",
     *       "updated_at": "2026-03-05T14:20:00.000000Z",
     *       "items": [
     *         {
     *           "id": 3,
     *           "product": {
     *             "id": 3,
     *             "name": "Teclado Mecânico",
     *             "price": "399.90"
     *           },
     *           "quantity": 1,
     *           "unit_price": "399.90",
     *           "subtotal": "399.90"
     *         }
     *       ]
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "per_page": 15,
     *     "total": 2,
     *     "last_page": 1,
     *     "from": 1,
     *     "to": 2
     *   }
     * }
     * @response 404 scenario="Cliente não encontrado" {
     *   "success": false,
     *   "message": "Cliente não encontrado"
     * }
     */
    public function sales(Customer $customer): JsonResponse
    {
        try {
            $sales = $this->customerService->getCustomerSales($customer, request());

            $transformedSales = clone $sales;
            $transformedSales->getCollection()->transform(function ($sale) {
                return new SaleResource($sale);
            });

            return $this->paginatedResponse($transformedSales);
        } catch (\Throwable $e) {
            return $this->serverErrorResponse('api.errors.server_error');
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DataTransferObjects\Sale\CreateSaleDTO;
use App\DataTransferObjects\Sale\UpdateSaleStatusDTO;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\SaleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @group 💰 Vendas
 *
 * Endpoints para gerenciamento completo de vendas com lógica de negócio complexa.
 * Inclui criação de vendas com múltiplos itens, controle de estoque, descontos e gestão de status.
 *
 * ## Status de Vendas
 *
 * As vendas seguem um fluxo de status bem definido:
 *
 * - **PENDING**: Venda criada, aguardando processamento
 * - **COMPLETED**: Venda finalizada, estoque deduzido
 * - **CANCELLED**: Venda cancelada, estoque restaurado
 *
 * ## Transições de Status Permitidas
 *
 * - PENDING → COMPLETED: Confirma a venda e deduz estoque
 * - PENDING → CANCELLED: Cancela a venda
 * - COMPLETED → CANCELLED: Cancela venda finalizada (restaura estoque)
 *
 * ## Lógica de Estoque
 *
 * - **Criação**: Reserva estoque (não deduz)
 * - **COMPLETED**: Deduz estoque definitivamente
 * - **CANCELLED**: Restaura estoque reservado/deduzido
 *
 * ## Sistema de Descontos
 *
 * - Desconto percentual aplicado sobre o subtotal
 * - Cálculo: subtotal - (subtotal * desconto / 100)
 * - Validação automática de produtos e quantidades
 */
class SaleController extends Controller
{
    public function __construct(
        private readonly SaleService $saleService
    ) {}

    /**
     * Listar vendas
     *
     * Retorna uma lista paginada de vendas com filtros opcionais.
     * Suporta filtros por status, cliente, período e valor.
     *
     * @authenticated
     *
     * @queryParam status string Filtrar por status da venda. Valores: pending, completed, cancelled. Example: completed
     * @queryParam customer_id integer Filtrar por ID do cliente. Example: 1
     * @queryParam date_from string Filtrar vendas a partir desta data (YYYY-MM-DD). Example: 2026-03-01
     * @queryParam date_to string Filtrar vendas até esta data (YYYY-MM-DD). Example: 2026-03-31
     * @queryParam min_total number Valor mínimo total da venda. Example: 100.00
     * @queryParam max_total number Valor máximo total da venda. Example: 5000.00
     * @queryParam per_page integer Número de itens por página (padrão: 15, máximo: 100). Example: 20
     * @queryParam page integer Página a ser exibida (padrão: 1). Example: 1
     *
     * @response 200 scenario="Lista de vendas" {
     *   "success": true,
     *   "message": "Dados recuperados com sucesso",
     *   "data": [
     *     {
     *       "id": 1,
     *       "customer": {
     *         "id": 1,
     *         "name": "João Silva",
     *         "email": "joao@exemplo.com",
     *         "cpf": "123.456.789-00"
     *       },
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
     *             "name": "Notebook Dell",
     *             "price": "2299.99"
     *           },
     *           "quantity": 1,
     *           "unit_price": "2299.99",
     *           "subtotal": "2299.99"
     *         },
     *         {
     *           "id": 2,
     *           "product": {
     *             "id": 2,
     *             "name": "Mouse Logitech",
     *             "price": "200.00"
     *           },
     *           "quantity": 1,
     *           "unit_price": "200.00",
     *           "subtotal": "200.00"
     *         }
     *       ]
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "per_page": 15,
     *     "total": 42,
     *     "last_page": 3,
     *     "from": 1,
     *     "to": 15
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $sales = $this->saleService->getPaginatedSales($request);

        $transformedSales = clone $sales;
        $transformedSales->getCollection()->transform(function ($sale) {
            return new SaleResource($sale);
        });

        return $this->paginatedResponse($transformedSales);
    }

    /**
     * Criar venda
     *
     * Cria uma venda com múltiplos produtos, aplicando validações de estoque e calculando descontos.
     *
     * ## Processo de Criação
     * 1. Valida disponibilidade de estoque para todos os produtos
     * 2. Reserva o estoque temporariamente
     * 3. Calcula subtotal, desconto e total
     * 4. Cria a venda com status PENDING
     * 5. Cria os itens da venda
     *
     * ## Validações Aplicadas
     * - **Estoque**: Verifica se há quantidade suficiente
     * - **Produtos**: Valida se todos os produtos existem
     * - **Quantidades**: Devem ser maiores que zero
     * - **Desconto**: Deve estar entre 0% e 100%
     *
     * @authenticated
     *
     * @bodyParam customer_id integer required ID do cliente. Deve existir na base de dados. Example: 1
     * @bodyParam discount_percentage number Percentual de desconto (0-100). Padrão: 0. Example: 10.50
     * @bodyParam items array required Lista de itens da venda. Mínimo 1 item.
     * @bodyParam items.*.product_id integer required ID do produto. Deve existir e ter estoque. Example: 1
     * @bodyParam items.*.quantity integer required Quantidade do produto. Deve ser > 0 e <= estoque disponível. Example: 2
     *
     * @response 201 scenario="Venda criada com sucesso" {
     *   "success": true,
     *   "message": "Venda criada com sucesso",
     *   "data": {
     *     "id": 1,
     *     "customer": {
     *       "id": 1,
     *       "name": "João Silva",
     *       "email": "joao@exemplo.com",
     *       "cpf": "123.456.789-00",
     *       "phone": "(11) 99999-9999"
     *     },
     *     "status": "pending",
     *     "subtotal": "2499.99",
     *     "discount_percentage": "10.50",
     *     "discount_amount": "262.50",
     *     "total": "2237.49",
     *     "items_count": 2,
     *     "created_at": "2026-03-06T10:30:00.000000Z",
     *     "updated_at": "2026-03-06T10:30:00.000000Z",
     *     "items": [
     *       {
     *         "id": 1,
     *         "product": {
     *           "id": 1,
     *           "name": "Notebook Dell Inspiron",
     *           "price": "2299.99",
     *           "image_url": "http://localhost:8080/storage/products/notebook.jpg"
     *         },
     *         "quantity": 1,
     *         "unit_price": "2299.99",
     *         "subtotal": "2299.99"
     *       },
     *       {
     *         "id": 2,
     *         "product": {
     *           "id": 2,
     *           "name": "Mouse Logitech",
     *           "price": "200.00",
     *           "image_url": "http://localhost:8080/storage/products/mouse.jpg"
     *         },
     *         "quantity": 1,
     *         "unit_price": "200.00",
     *         "subtotal": "200.00"
     *       }
     *     ]
     *   }
     * }
     * @response 422 scenario="Estoque insuficiente" {
     *   "success": false,
     *   "message": "Estoque insuficiente para um ou mais produtos"
     * }
     * @response 422 scenario="Erro de validação" {
     *   "success": false,
     *   "message": "Erro na validação dos dados",
     *   "errors": {
     *     "customer_id": ["O cliente selecionado é inválido."],
     *     "items.0.quantity": ["A quantidade deve ser maior que 0."],
     *     "discount_percentage": ["O desconto deve estar entre 0 e 100."]
     *   }
     * }
     * @response 400 scenario="Falha na criação" {
     *   "success": false,
     *   "message": "Falha ao criar a venda",
     *   "errors": {
     *     "details": "Erro específico do sistema"
     *   }
     * }
     */
    public function store(StoreSaleRequest $request): JsonResponse
    {
        try {
            $dto = CreateSaleDTO::fromArray($request->validated());
            $sale = $this->saleService->createSale($dto);

            return $this->createdResponse(
                new SaleResource($sale),
                'api.success.sale_created'
            );

        } catch (InsufficientStockException|SaleException $e) {
            return $this->errorResponse($e->getMessage());

        } catch (\Throwable $e) {
            return $this->serverErrorResponse('api.errors.server_error');
        }
    }

    /**
     * Exibir venda
     *
     * Retorna os detalhes completos de uma venda específica, incluindo todos os itens,
     * informações do cliente e cálculos detalhados.
     *
     * @authenticated
     *
     * @urlParam id integer required ID da venda. Example: 1
     *
     * @response 200 scenario="Venda encontrada" {
     *   "success": true,
     *   "message": "Dados recuperados com sucesso",
     *   "data": {
     *     "id": 1,
     *     "customer": {
     *       "id": 1,
     *       "name": "João Silva",
     *       "email": "joao@exemplo.com",
     *       "cpf": "123.456.789-00",
     *       "phone": "(11) 99999-9999"
     *     },
     *     "status": "completed",
     *     "subtotal": "2499.99",
     *     "discount_percentage": "10.50",
     *     "discount_amount": "262.50",
     *     "total": "2237.49",
     *     "items_count": 2,
     *     "created_at": "2026-03-06T10:30:00.000000Z",
     *     "updated_at": "2026-03-06T11:00:00.000000Z",
     *     "items": [
     *       {
     *         "id": 1,
     *         "product": {
     *           "id": 1,
     *           "name": "Notebook Dell Inspiron",
     *           "price": "2299.99",
     *           "image_url": "http://localhost:8080/storage/products/notebook.jpg"
     *         },
     *         "quantity": 1,
     *         "unit_price": "2299.99",
     *         "subtotal": "2299.99"
     *       },
     *       {
     *         "id": 2,
     *         "product": {
     *           "id": 2,
     *           "name": "Mouse Logitech",
     *           "price": "200.00",
     *           "image_url": "http://localhost:8080/storage/products/mouse.jpg"
     *         },
     *         "quantity": 1,
     *         "unit_price": "200.00",
     *         "subtotal": "200.00"
     *       }
     *     ]
     *   }
     * }
     * @response 404 scenario="Venda não encontrada" {
     *   "success": false,
     *   "message": "Venda não encontrada"
     * }
     */
    public function show(Sale $sale): JsonResponse
    {
        $saleWithRelations = $this->saleService->findSaleWithRelations($sale->id);

        return $this->successResponse(new SaleResource($saleWithRelations));
    }

    /**
     * Atualizar status da venda
     *
     * Altera o status de uma venda seguindo as regras de transição de status.
     * Esta é uma operação crítica que afeta o controlo de estoque.
     *
     * ## Transições de Status
     *
     * ### PENDING → COMPLETED
     * - Confirma a venda
     * - Deduz estoque definitivamente
     * - Não pode ser revertido automaticamente
     *
     * ### PENDING → CANCELLED
     * - Cancela a venda
     * - Libera estoque reservado
     * - Operação definitiva
     *
     * ### COMPLETED → CANCELLED
     * - Cancela venda já finalizada
     * - Restaura estoque deduzido
     * - Requer verificação de estoque atual
     *
     * ## Validações de Negócio
     * - Verifica transição válida de status
     * - Valida disponibilidade de estoque (para restauração)
     * - Aplica regras de auditoria
     *
     * @authenticated
     *
     * @urlParam id integer required ID da venda. Example: 1
     *
     * @bodyParam status string required Novo status da venda. Valores: pending, completed, cancelled. Example: completed
     *
     * @response 200 scenario="Status atualizado - PENDING para COMPLETED" {
     *   "success": true,
     *   "message": "Status da venda atualizado com sucesso",
     *   "data": {
     *     "id": 1,
     *     "customer": {
     *       "id": 1,
     *       "name": "João Silva",
     *       "email": "joao@exemplo.com",
     *       "cpf": "123.456.789-00"
     *     },
     *     "status": "completed",
     *     "subtotal": "2499.99",
     *     "discount_percentage": "10.50",
     *     "discount_amount": "262.50",
     *     "total": "2237.49",
     *     "items_count": 2,
     *     "created_at": "2026-03-06T10:30:00.000000Z",
     *     "updated_at": "2026-03-06T11:00:00.000000Z",
     *     "items": [
     *       {
     *         "id": 1,
     *         "product": {
     *           "id": 1,
     *           "name": "Notebook Dell Inspiron",
     *           "price": "2299.99"
     *         },
     *         "quantity": 1,
     *         "unit_price": "2299.99",
     *         "subtotal": "2299.99"
     *       }
     *     ]
     *   }
     * }
     * @response 404 scenario="Venda não encontrada" {
     *   "success": false,
     *   "message": "Venda não encontrada"
     * }
     * @response 400 scenario="Transição de status inválida" {
     *   "success": false,
     *   "message": "Transição de status inválida"
     * }
     * @response 422 scenario="Estoque insuficiente para cancelar" {
     *   "success": false,
     *   "message": "Estoque insuficiente para completar a operação"
     * }
     * @response 422 scenario="Campo status obrigatório" {
     *   "success": false,
     *   "message": "Nenhum campo válido para atualização fornecido"
     * }
     * @response 400 scenario="Falha na atualização" {
     *   "success": false,
     *   "message": "Falha ao atualizar a venda",
     *   "errors": {
     *     "details": "Erro específico do sistema"
     *   }
     * }
     */
    public function update(UpdateSaleRequest $request, Sale $sale): JsonResponse
    {
        try {
            $dto = UpdateSaleStatusDTO::fromArray($request->validated());
            $updatedSale = $this->saleService->updateStatus($sale, $dto);

            return $this->successWithDataResponse(
                new SaleResource($updatedSale->load(['customer', 'items.product'])),
                'api.success.sale_status_updated'
            );

        } catch (InvalidStatusTransitionException|InsufficientStockException|SaleException $e) {
            return $this->errorResponse($e->getMessage());

        } catch (\Throwable $e) {

            dd($e);
            return $this->serverErrorResponse('api.errors.server_error');
        }
    }

    /**
     * Excluir venda
     *
     * Remove uma venda do sistema com todas as suas validações e impactos.
     *
     * ## Processo de Exclusão
     * 1. Verifica se a venda pode ser excluída
     * 2. Restaura estoque se necessário (dependendo do status)
     * 3. Remove itens da venda
     * 4. Remove a venda
     *
     * ## Regras de Negócio
     * - Vendas PENDING: Remove e libera estoque reservado
     * - Vendas COMPLETED: Remove e restaura estoque deduzido
     * - Vendas CANCELLED: Remove sem impacto no estoque
     *
     * ## Impacto no Estoque
     * - **PENDING**: Libera estoque reservado
     * - **COMPLETED**: Adiciona de volta ao estoque
     * - **CANCELLED**: Sem alteração (já estava liberado)
     *
     * @authenticated
     *
     * @urlParam id integer required ID da venda. Example: 1
     *
     * @response 200 scenario="Venda excluída" {
     *   "success": true,
     *   "message": "Venda excluída com sucesso",
     *   "data": null
     * }
     * @response 404 scenario="Venda não encontrada" {
     *   "success": false,
     *   "message": "Venda não encontrada"
     * }
     * @response 400 scenario="Falha na exclusão" {
     *   "success": false,
     *   "message": "Falha ao criar a venda",
     *   "errors": {
     *     "details": "Erro específico do sistema"
     *   }
     * }
     */
    public function destroy(Sale $sale): JsonResponse
    {
        try {
            $this->saleService->deleteSale($sale);

            return $this->successResponse(null, 'api.success.sale_deleted');

        } catch (SaleException $e) {
            return $this->errorResponse('api.errors.sale_creation_failed', ['details' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return $this->serverErrorResponse('api.errors.server_error');
        }
    }

    /**
     * Listar itens de uma venda
     *
     * Retorna todos os itens de uma venda específica com detalhes dos produtos.
     * Útil para exibir o detalhamento completo dos produtos vendidos.
     *
     * @authenticated
     *
     * @urlParam sale integer required ID da venda. Example: 1
     *
     * @response 200 scenario="Itens da venda" {
     *   "Success": true,
     *   "message": "Dados recuperados com sucesso",
     *   "data": [
     *     {
     *       "id": 1,
     *       "product": {
     *         "id": 1,
     *         "name": "Notebook Dell Inspiron",
     *         "description": "Notebook Dell com processador i7",
     *         "price": "2299.99",
     *         "image_url": "http://localhost:8080/storage/products/notebook.jpg"
     *       },
     *       "quantity": 1,
     *       "unit_price": "2299.99",
     *       "subtotal": "2299.99"
     *     },
     *     {
     *       "id": 2,
     *       "product": {
     *         "id": 2,
     *         "name": "Mouse Logitech",
     *         "description": "Mouse sem fio com precisão ótica",
     *         "price": "200.00",
     *         "image_url": "http://localhost:8080/storage/products/mouse.jpg"
     *       },
     *       "quantity": 1,
     *       "unit_price": "200.00",
     *       "subtotal": "200.00"
     *     }
     *   ]
     * }
     * @response 404 scenario="Venda não encontrada" {
     *   "success": false,
     *   "message": "Venda não encontrada"
     * }
     */
    public function items(Sale $sale): JsonResponse
    {
        $saleWithItems = $this->saleService->findSaleWithRelations($sale->id);

        return $this->successResponse($saleWithItems->items);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DataTransferObjects\Product\CreateProductDTO;
use App\DataTransferObjects\Product\UpdateProductDTO;
use App\Exceptions\ProductException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductQueryService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 📦 Produtos
 *
 * Endpoints para gerenciamento completo de produtos, incluindo CRUD, upload de imagens e controle de estoque.
 * Todos os endpoints são protegidos por autenticação e possuem validação rigorosa de dados.
 */
class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly ProductQueryService $productQueryService
    ) {}

    /**
     * Listar produtos
     *
     * Retorna uma lista paginada de produtos com opções de filtro e busca.
     * Suporta filtros por nome, categoria e faixa de preço.
     *
     * @authenticated
     *
     * @queryParam search string Filtro de busca por nome do produto. Example: notebook
     * @queryParam min_price number Preço mínimo para filtrar produtos. Example: 100.00
     * @queryParam max_price number Preço máximo para filtrar produtos. Example: 1000.00
     * @queryParam per_page integer Número de itens por página (padrão: 15, máximo: 100). Example: 20
     * @queryParam page integer Página a ser exibida (padrão: 1). Example: 1
     *
     * @response 200 scenario="Lista de produtos" {
     *   "success": true,
     *   "message": "Dados recuperados com sucesso",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Notebook Dell Inspiron",
     *       "description": "Notebook Dell com processador i7 e 16GB RAM",
     *       "price": "2499.99",
     *       "stock_quantity": 15,
     *       "image_url": "http://localhost:8080/storage/products/notebook_dell.jpg",
     *       "created_at": "2026-03-06T10:30:00.000000Z",
     *       "updated_at": "2026-03-06T10:30:00.000000Z"
     *     },
     *     {
     *       "id": 2,
     *       "name": "Mouse Logitech MX",
     *       "description": "Mouse sem fio com precisão óptica",
     *       "price": "199.90",
     *       "stock_quantity": 50,
     *       "image_url": "http://localhost:8080/storage/products/mouse_logitech.jpg",
     *       "created_at": "2026-03-06T11:00:00.000000Z",
     *       "updated_at": "2026-03-06T11:00:00.000000Z"
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "per_page": 15,
     *     "total": 50,
     *     "last_page": 4,
     *     "from": 1,
     *     "to": 15
     *   }
     * }
     * @response 401 scenario="Token inválido" {
     *   "success": false,
     *   "message": "Token de acesso inválido ou expirado"
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $products = $this->productQueryService->getPaginatedProducts($request);

        $transformedProducts = clone $products;
        $transformedProducts->getCollection()->transform(function ($product) {
            return new ProductResource($product);
        });

        return $this->paginatedResponse($transformedProducts);
    }

    /**
     * Criar produto
     *
     * Cria um produto no sistema com validação completa de dados.
     * Suporta upload de imagem opcional.
     *
     * @authenticated
     *
     * @bodyParam name string required Nome do produto. Deve ser único. Example: Notebook Dell Inspiron
     * @bodyParam description string required Descrição detalhada do produto. Example: Notebook Dell com processador i7, 16GB RAM e SSD 512GB
     * @bodyParam price number required Preço do produto (máximo 2 decimais). Deve ser maior que 0. Example: 2499.99
     * @bodyParam stock_quantity integer required Quantidade em estoque. Deve ser >= 0. Example: 15
     * @bodyParam image file Imagem do produto. Formatos aceitos: jpg, jpeg, png, gif. Tamanho máximo: 2MB.
     *
     * @response 201 scenario="Produto criado com sucesso" {
     *   "Success": true,
     *   "message": "Produto criado com sucesso",
     *   "data": {
     *     "id": 1,
     *     "name": "Notebook Dell Inspiron",
     *     "description": "Notebook Dell com processador i7, 16GB RAM e SSD 512GB",
     *     "price": "2499.99",
     *     "stock_quantity": 15,
     *     "image_url": "http://localhost:8080/storage/products/notebook_dell.jpg",
     *     "created_at": "2026-03-06T10:30:00.000000Z",
     *     "updated_at": "2026-03-06T10:30:00.000000Z"
     *   }
     * }
     * @response 422 scenario="Erro de validação" {
     *   "success": false,
     *   "message": "Erro na validação dos dados",
     *   "errors": {
     *     "name": ["O campo nome é obrigatório."],
     *     "price": ["O campo preço deve ser maior que 0."],
     *     "stock_quantity": ["O campo quantidade deve ser um número inteiro."]
     *   }
     * }
     * @response 422 scenario="Produto já existe" {
     *   "success": false,
     *   "message": "Erro na validação dos dados",
     *   "errors": {
     *     "name": ["O produto com este nome já existe."]
     *   }
     * }
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $dto = CreateProductDTO::fromArray($request->validated());
            $product = $this->productService->createProduct($dto);

            return $this->createdResponse(
                new ProductResource($product),
                'api.success.product_created'
            );
        } catch (ProductException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable $e) {
            return $this->serverErrorResponse('api.errors.server_error');
        }
    }

    /**
     * Exibir produto
     *
     * Retorna os detalhes completos de um produto específico.
     *
     * @authenticated
     *
     * @urlParam id integer required ID do produto. Example: 1
     *
     * @response 200 scenario="Produto encontrado" {
     *   "Success": true,
     *   "message": "Dados recuperados com sucesso",
     *   "data": {
     *     "id": 1,
     *     "name": "Notebook Dell Inspiron",
     *     "description": "Notebook Dell com processador i7, 16GB RAM e SSD 512GB",
     *     "price": "2499.99",
     *     "stock_quantity": 15,
     *     "image_url": "http://localhost:8080/storage/products/notebook_dell.jpg",
     *     "created_at": "2026-03-06T10:30:00.000000Z",
     *     "updated_at": "2026-03-06T10:30:00.000000Z"
     *   }
     * }
     * @response 404 scenario="Produto não encontrado" {
     *   "success": false,
     *   "message": "Produto não encontrado"
     * }
     */
    public function show(Product $product): JsonResponse
    {
        return $this->successResponse(new ProductResource($product));
    }

    /**
     * Atualizar produto
     *
     * Atualiza os dados de um produto existente.
     * Permite atualização parcial dos campos e substituição da imagem.
     *
     * @authenticated
     *
     * @urlParam id integer required ID do produto. Example: 1
     *
     * @bodyParam name string Nome do produto. Deve ser único (exceto para o próprio produto). Example: Notebook Dell Inspiron 15
     * @bodyParam description string Descrição atualizada do produto. Example: Notebook Dell com processador i7 atualizado
     * @bodyParam price number Novo preço do produto (máximo 2 decimais). Deve ser maior que 0. Example: 2699.99
     * @bodyParam stock_quantity integer Nova quantidade em estoque. Deve ser >= 0. Example: 20
     * @bodyParam image file Nova imagem do produto (substitui a anterior). Formatos aceitos: jpg, jpeg, png, gif. Tamanho máximo: 2MB.
     *
     * @response 200 scenario="Produto atualizado" {
     *   "Success": true,
     *   "message": "Produto atualizado com sucesso",
     *   "data": {
     *     "id": 1,
     *     "name": "Notebook Dell Inspiron 15",
     *     "description": "Notebook Dell com processador i7 atualizado",
     *     "price": "2699.99",
     *     "stock_quantity": 20,
     *     "image_url": "http://localhost:8080/storage/products/notebook_dell_updated.jpg",
     *     "created_at": "2026-03-06T10:30:00.000000Z",
     *     "updated_at": "2026-03-06T12:30:00.000000Z"
     *   }
     * }
     * @response 404 scenario="Produto não encontrado" {
     *   "success": false,
     *   "message": "Produto não encontrado"
     * }
     * @response 422 scenario="Erro de validação" {
     *   "success": false,
     *   "message": "Erro na validação dos dados",
     *   "errors": {
     *     "name": ["O produto com este nome já existe."],
     *     "price": ["O campo preço deve ser maior que 0."]
     *   }
     * }
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        try {
            $dto = UpdateProductDTO::fromArray($request->validated());
            $updatedProduct = $this->productService->updateProduct($product, $dto);

            return $this->successWithDataResponse(
                new ProductResource($updatedProduct),
                'api.success.product_updated'
            );
        } catch (ProductException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable $e) {
            return $this->serverErrorResponse('api.errors.server_error');
        }
    }

    /**
     * Excluir produto
     *
     * Remove um produto do sistema.
     * Não é possível excluir produtos que possuem vendas associadas (regra de negócio).
     * A imagem do produto também será removida do armazenamento.
     *
     * @authenticated
     *
     * @urlParam id integer required ID do produto. Example: 1
     *
     * @response 200 scenario="Produto excluído" {
     *   "Success": true,
     *   "message": "Produto excluído com sucesso",
     *   "data": null
     * }
     * @response 404 scenario="Produto não encontrado" {
     *   "success": false,
     *   "message": "Produto não encontrado"
     * }
     * @response 409 scenario="Produto possui vendas" {
     *   "success": false,
     *   "message": "Não é possível excluir o produto, pois ele possui vendas associadas"
     * }
     */
    public function destroy(Product $product): JsonResponse
    {
        try {
            $deleted = $this->productService->deleteProduct($product);

            if ($deleted) {
                return $this->successResponse(null, 'api.success.product_deleted');
            }

            return $this->errorResponse('api.errors.product_delete_failed');
        } catch (ProductException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable $e) {
            return $this->serverErrorResponse('api.errors.server_error');
        }
    }
}

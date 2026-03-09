<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ProductRepositoryInterface;
use App\DataTransferObjects\Product\CreateProductDTO;
use App\DataTransferObjects\Product\UpdateProductDTO;
use App\Exceptions\ProductException;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

readonly class ProductService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ImageUploadService $imageUploadService
    ) {}

    /**
     * @throws ProductException
     * @throws \Throwable
     */
    public function createProduct(CreateProductDTO $dto): Product
    {
        return DB::transaction(function () use ($dto) {
            $data = $dto->toArray();

            if ($dto->image instanceof UploadedFile) {
                $imagePath = $this->imageUploadService->upload($dto->image);
                $data['image_path'] = $imagePath;
            }

            return $this->productRepository->create($data);
        });
    }

    /**
     * @throws ProductException
     * @throws \Throwable
     */
    public function updateProduct(Product $product, UpdateProductDTO $dto): Product
    {
        if (! $dto->hasUpdates()) {
            throw new ProductException('Nenhuma atualização foi fornecida para o produto');
        }

        return DB::transaction(function () use ($product, $dto) {
            $data = $dto->toArray();

            if ($dto->image instanceof UploadedFile) {
                if ($product->image_path) {
                    $this->imageUploadService->delete($product->image_path);
                }

                $imagePath = $this->imageUploadService->upload($dto->image);
                $data['image_path'] = $imagePath;
            }

            $this->productRepository->update($product, $data);

            return $product->fresh();
        });
    }

    /**
     * @throws ProductException
     * @throws \Throwable
     */
    public function deleteProduct(Product $product): bool
    {
        if ($this->productRepository->hasAssociatedSales($product)) {
            throw new ProductException('Não é possível excluir produto com vendas associadas');
        }

        return DB::transaction(function () use ($product) {
            if ($product->image_path) {
                $this->imageUploadService->delete($product->image_path);
            }

            return $this->productRepository->delete($product);
        });
    }

    /**
     * @throws ProductException
     */
    public function adjustStock(Product $product, int $adjustment): Product
    {
        if ($adjustment === 0) {
            throw new ProductException('Ajuste de estoque deve ser diferente de zero');
        }

        if ($adjustment < 0 && abs($adjustment) > $product->stock_quantity) {
            throw new ProductException(
                "Não é possível reduzir estoque abaixo de zero. Estoque atual: {$product->stock_quantity}, Redução solicitada: " . abs($adjustment)
            );
        }

        if ($adjustment > 0) {
            $this->productRepository->incrementStock($product, $adjustment);
        } else {
            $this->productRepository->decrementStock($product, abs($adjustment));
        }

        return $product->fresh();
    }

    public function getProductWithStats(int $productId): Model
    {
        return $this->productRepository->find($productId);
    }

    public function canDelete(Product $product): bool
    {
        return ! $this->productRepository->hasAssociatedSales($product);
    }
}

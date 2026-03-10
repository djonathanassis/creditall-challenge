<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ProductRepositoryInterface;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use Illuminate\Support\Arr;

readonly class InventoryService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {}

    /**

     * @param array $items
     * @throws InsufficientStockException
     */
    public function bulkValidateStock(array $items): void
    {
        foreach ($items as $item) {
            /** @var Product $product */
            $product = $this->productRepository->find($item->productId);

            if (!$product) {
                throw new InsufficientStockException(
                    "Produto #{$item->productId}",
                    $item->quantity,
                    0
                );
            }

            $this->validateInsufficientStock($product, $item->quantity);
        }
    }

    /**
     * @throws InsufficientStockException
     */
    public function reserveStock(Product $product, int $quantity): void
    {
        [$product, $quantity] = $this->validateInsufficientStock($product, $quantity);
        $this->productRepository->decrementStock($product, $quantity);
    }

    public function releaseStock(Product $product, int $quantity): void
    {
        $this->productRepository->incrementStock($product, $quantity);
    }

    /**
     * @param array $items
     * @throws InsufficientStockException
     */
    public function bulkReserveStock(array $items): void
    {
        foreach ($items as $item) {
            /** @var Product $product */
            $product = $this->productRepository->find($item->productId);

            if (!$product) {
                throw new InsufficientStockException(
                    "Produto #{$item->productId}",
                    $item->quantity,
                    0
                );
            }

            $this->reserveStock($product, $item->quantity);
        }
    }

    public function bulkReleaseStock(array $saleItems): void
    {
        foreach ($saleItems as $saleItem) {
            $this->releaseStock($saleItem->product, $saleItem->quantity);
        }
    }

    private function validateQuantity(Product $product, int $quantity): bool
    {
        return $product->stock_quantity >= $quantity;
    }

    /**
     * @throws InsufficientStockException
     */
    private function validateInsufficientStock(Product $product, int $quantity): array
    {
        if (!$this->validateQuantity($product, $quantity)) {
            throw new InsufficientStockException(
                $product->name,
                $quantity,
                $product->stock_quantity
            );
        }

        return [$product, $quantity];
    }
}

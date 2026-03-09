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

    public function validateStock(Product $product, int $quantity): bool
    {
        return $product->stock_quantity >= $quantity;
    }

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

            if (!$this->validateStock($product, $item->quantity)) {
                throw new InsufficientStockException(
                    $product->name,
                    $item->quantity,
                    $product->stock_quantity
                );
            }
        }
    }

    /**
     * @throws InsufficientStockException
     */
    public function reserveStock(Product $product, int $quantity): void
    {
        if (! $this->validateStock($product, $quantity)) {
            throw new InsufficientStockException(
                $product->name,
                $quantity,
                $product->stock_quantity
            );
        }

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
}

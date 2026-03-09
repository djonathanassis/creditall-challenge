<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use Illuminate\Support\Collection;

interface HasStockInterface
{
    /**
     * @param Product $product
     * @param int $quantity
     * @return void
     */
    public function decrementStock(Product $product, int $quantity): void;

    /**
     * @param Product $product
     * @param int $quantity
     * @return void
     */
    public function incrementStock(Product $product, int $quantity): void;

    /**
     * @param Product $product
     * @param int $requiredQuantity
     * @return bool
     */
    public function hasSufficientStock(Product $product, int $requiredQuantity): bool;

    /**
     * @param int $threshold
     * @return Collection
     */
    public function getLowStockProducts(int $threshold = 10): Collection;
}

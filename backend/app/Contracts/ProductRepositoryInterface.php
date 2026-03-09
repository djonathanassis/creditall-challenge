<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Product;
use Illuminate\Support\Collection;

interface ProductRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param Product $model
     * @return bool
     */
    public function hasAssociatedSales(Product $model): bool;

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
}

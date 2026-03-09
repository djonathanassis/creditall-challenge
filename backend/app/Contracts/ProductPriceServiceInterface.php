<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Exceptions\SaleException;

interface ProductPriceServiceInterface
{
    /**
     * @param array<int> $productIds
     * @return array<int, float>
     * @throws SaleException
     */
    public function getPricesForProducts(array $productIds): array;

    /**
     * @param int $productId
     * @return float
     * @throws SaleException
     */
    public function getPriceForProduct(int $productId): float;
}

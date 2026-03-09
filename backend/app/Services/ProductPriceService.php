<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ProductPriceServiceInterface;
use App\Exceptions\SaleException;
use App\Models\Product;

final class ProductPriceService implements ProductPriceServiceInterface
{
    /**
     * @var array<int, float>
     */
    private array $priceCache = [];

    /**
     * @param array $productIds
     * @return array|float[]
     * @throws SaleException
     */
    public function getPricesForProducts(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $uniqueIds = array_unique($productIds);
        $missingIds = array_diff($uniqueIds, array_keys($this->priceCache));

        // Only query database for missing prices
        if (! empty($missingIds)) {
            $prices = Product::whereIn('id', $missingIds)
                ->pluck('price', 'id')
                ->toArray();

            if (count($prices) !== count($missingIds)) {
                $notFoundIds = array_diff($missingIds, array_keys($prices));
                throw new SaleException('Produtos não encontrados: '.implode(', ', $notFoundIds));
            }

            // Use + operator to preserve numeric string keys (array_merge re-indexes them)
            $this->priceCache = $this->priceCache + $prices;
        }

        return array_intersect_key($this->priceCache, array_flip($uniqueIds));
    }

    /**
     * @param int $productId
     * @return float
     * @throws SaleException
     */
    public function getPriceForProduct(int $productId): float
    {
        if (!isset($this->priceCache[$productId])) {
            $prices = $this->getPricesForProducts([$productId]);
            if (empty($prices)) {
                throw new SaleException("Produto não encontrado (ID: {$productId})");
            }
        }

        return (float) $this->priceCache[$productId];
    }

    /**
     * @return void
     */
    public function clearCache(): void
    {
        $this->priceCache = [];
    }

    /**
     * @return array<int, float>
     */
    public function getCacheState(): array
    {
        return $this->priceCache;
    }
}

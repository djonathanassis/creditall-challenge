<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DataTransferObjects\Sale\SaleItemDTO;

interface SaleCalculationServiceInterface
{
    /**
     * @param SaleItemDTO $item
     * @return float
     */
    public function calculateItemSubtotal(SaleItemDTO $item): float;

    /**
     * @param array<SaleItemDTO> $items
     * @param float $discountPercentage
     * @return array{subtotal: float, discount_amount: float, total_amount: float}
     */
    public function calculateSaleTotals(array $items, float $discountPercentage): array;

    /**
     * @param float $subtotal
     * @param float $discountPercentage
     * @return float
     */
    public function calculateDiscountAmount(float $subtotal, float $discountPercentage): float;
}

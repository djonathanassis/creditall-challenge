<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SaleCalculationServiceInterface;
use App\DataTransferObjects\Sale\SaleItemDTO;

class SaleCalculationService implements SaleCalculationServiceInterface
{
    /**
     * {@inheritdoc}
     */
    public function calculateItemSubtotal(SaleItemDTO $item): float
    {
        return $item->unitPrice * $item->quantity;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateSaleTotals(array $items, float $discountPercentage): array
    {
        $subtotal = 0.0;

        foreach ($items as $item) {
            $subtotal += $this->calculateItemSubtotal($item);
        }

        $discountAmount = $this->calculateDiscountAmount($subtotal, $discountPercentage);
        $totalAmount = $subtotal - $discountAmount;

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function calculateDiscountAmount(float $subtotal, float $discountPercentage): float
    {
        return $subtotal * ($discountPercentage / 100);
    }
}

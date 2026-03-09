<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Sale;

use App\DataTransferObjects\Shared\BaseDTO;

final class SaleItemDTO extends BaseDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly int $quantity,
        public readonly ?float $unitPrice = null
    ) {}

    /**
     * @param array<string, mixed> $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: (int) $data['product_id'],
            quantity: (int) $data['quantity'],
            unitPrice: isset($data['unit_price']) ? (float) $data['unit_price'] : null
        );
    }
}

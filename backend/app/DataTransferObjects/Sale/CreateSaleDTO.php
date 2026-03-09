<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Sale;

use App\DataTransferObjects\Shared\BaseDTO;

final class CreateSaleDTO extends BaseDTO
{
    public function __construct(
        public readonly int $customerId,
        public readonly float $discountPercentage,
        /** @var SaleItemDTO[] */
        public readonly array $items
    ) {}

    /**
     * @param array<string, mixed> $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        $items = array_map(
            static fn (array $itemData) => SaleItemDTO::fromArray($itemData),
            $data['items'] ?? []
        );

        return new self(
            customerId: (int) $data['customer_id'],
            discountPercentage: (float) ($data['discount_percentage'] ?? 0.0),
            items: $items
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'discount_percentage' => $this->discountPercentage,
            'items' => array_map(
                static fn (SaleItemDTO $item) => $item->toArray(),
                $this->items
            ),
        ];
    }
}

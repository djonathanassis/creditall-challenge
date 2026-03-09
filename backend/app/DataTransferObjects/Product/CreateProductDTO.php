<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Product;

use App\DataTransferObjects\Shared\BaseDTO;
use Illuminate\Http\UploadedFile;

final class CreateProductDTO extends BaseDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly float $price,
        public readonly int $stockQuantity,
        public readonly UploadedFile|string|null $image = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            price: (float) $data['price'],
            stockQuantity: (int) $data['stock_quantity'],
            image: $data['image'] ?? null,
        );
    }

    /**
     * @return static
     */
    public function withImagePath(string $imagePath): self
    {
        return new self(
            name: $this->name,
            description: $this->description,
            price: $this->price,
            stockQuantity: $this->stockQuantity,
            image: $imagePath,
        );
    }
}

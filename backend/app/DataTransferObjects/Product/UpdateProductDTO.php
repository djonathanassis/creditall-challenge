<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Product;

use App\DataTransferObjects\Shared\BaseDTO;
use Illuminate\Http\UploadedFile;

final class UpdateProductDTO extends BaseDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?float $price = null,
        public readonly ?int $stockQuantity = null,
        public readonly UploadedFile|string|null $image = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            price: isset($data['price']) ? (float) $data['price'] : null,
            stockQuantity: isset($data['stock_quantity']) ? (int) $data['stock_quantity'] : null,
            image: $data['image'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = [];

        if ($this->name !== null) {
            $array['name'] = $this->name;
        }

        if ($this->description !== null) {
            $array['description'] = $this->description;
        }

        if ($this->price !== null) {
            $array['price'] = $this->price;
        }

        if ($this->stockQuantity !== null) {
            $array['stock_quantity'] = $this->stockQuantity;
        }

        if ($this->image !== null) {
            $array['image'] = $this->image;
        }

        return $array;
    }

    public function hasUpdates(): bool
    {
        return ! empty($this->toArray());
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

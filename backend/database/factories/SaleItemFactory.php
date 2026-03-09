<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SaleItem>
 */
class SaleItemFactory extends Factory
{
    protected $model = SaleItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 5);
        $unitPrice = $this->faker->randomFloat(2, 10.00, 200.00);
        $subtotal = $quantity * $unitPrice;

        return [
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
        ];
    }

    /**
     * Create a sale item with a specific quantity.
     */
    public function quantity(int $quantity): static
    {
        return $this->state(function (array $attributes) use ($quantity) {
            $unitPrice = $attributes['unit_price'];
            $subtotal = $quantity * $unitPrice;

            return [
                'quantity' => $quantity,
                'subtotal' => $subtotal,
            ];
        });
    }

    /**
     * Create a sale item with a specific unit price.
     */
    public function unitPrice(float $price): static
    {
        return $this->state(function (array $attributes) use ($price) {
            $quantity = $attributes['quantity'];
            $subtotal = $quantity * $price;

            return [
                'unit_price' => $price,
                'subtotal' => $subtotal,
            ];
        });
    }
}

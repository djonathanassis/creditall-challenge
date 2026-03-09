<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 50.00, 1000.00);
        $discountPercentage = $this->faker->numberBetween(0, 20);
        $discountAmount = ($subtotal * $discountPercentage) / 100;
        $totalAmount = $subtotal - $discountAmount;

        return [
            'customer_id' => Customer::factory(),
            'subtotal' => $subtotal,
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'status' => $this->faker->randomElement([
                SaleStatus::PENDING,
                SaleStatus::COMPLETED,
                SaleStatus::CANCELLED,
            ]),
        ];
    }

    /**
     * Indicate that the sale is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SaleStatus::PENDING,
        ]);
    }

    /**
     * Indicate that the sale is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SaleStatus::COMPLETED,
        ]);
    }

    /**
     * Indicate that the sale is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SaleStatus::CANCELLED,
        ]);
    }

    /**
     * Indicate that the sale has a discount.
     */
    public function withDiscount(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = $attributes['subtotal'];
            $discountPercentage = $this->faker->numberBetween(5, 25);
            $discountAmount = ($subtotal * $discountPercentage) / 100;
            $totalAmount = $subtotal - $discountAmount;

            return [
                'discount_percentage' => $discountPercentage,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
            ];
        });
    }

    /**
     * Indicate that the sale has no discount.
     */
    public function withoutDiscount(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = $attributes['subtotal'];

            return [
                'discount_percentage' => 0,
                'discount_amount' => 0,
                'total_amount' => $subtotal,
            ];
        });
    }
}

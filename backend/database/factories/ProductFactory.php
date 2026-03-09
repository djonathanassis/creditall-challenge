<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Electronics', 'Clothing', 'Books', 'Home & Garden', 'Sports', 'Toys', 'Beauty', 'Automotive'];
        $adjectives = ['Premium', 'Professional', 'Advanced', 'Classic', 'Modern', 'Portable', 'Wireless', 'Smart'];

        $category = $this->faker->randomElement($categories);
        $adjective = $this->faker->randomElement($adjectives);

        return [
            'name' => $adjective . ' ' . $category . ' ' . $this->faker->word(),
            'description' => $this->faker->paragraph(2),
            'price' => $this->faker->randomFloat(2, 10.00, 999.99),
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'image_path' => null, // Will be set when actually uploading images
        ];
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
        ]);
    }

    /**
     * Indicate that the product has low stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => $this->faker->numberBetween(1, 5),
        ]);
    }

    /**
     * Indicate that the product is expensive.
     */
    public function expensive(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $this->faker->randomFloat(2, 500.00, 2000.00),
        ]);
    }

    /**
     * Indicate that the product has an image.
     */
    public function withImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'image_path' => 'products/sample-' . $this->faker->uuid() . '.jpg',
        ]);
    }
}

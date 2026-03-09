<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }

    public function testCanListProducts(): void
    {
        Product::factory()->count(5)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'price',
                        'stock_quantity',
                        'image_url',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta',
            ]);
    }

    public function testCanCreateProduct(): void
    {
        $productData = [
            'name' => 'Test Product',
            'description' => 'A test product description',
            'price' => 99.99,
            'stock_quantity' => 10,
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'success' => true,
                'message' => 'Produto criado com sucesso',
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'price' => 99.99,
            'stock_quantity' => 10,
        ]);
    }

    public function testCanShowProduct(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
            ])
            ->assertJsonFragment([
                'id' => $product->id,
                'name' => $product->name,
            ]);
    }

    public function testCanUpdateProduct(): void
    {
        $product = Product::factory()->create();

        $updateData = [
            'name' => 'Updated Product Name',
            'price' => 149.99,
        ];

        $response = $this->putJson("/api/products/{$product->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'message' => 'Produto atualizado com sucesso',
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'price' => 149.99,
        ]);
    }

    public function testCanDeleteProduct(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'message' => 'Produto excluído com sucesso',
            ]);

        // Verify product is soft deleted, not hard deleted
        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);
    }

    public function testProductValidationRules(): void
    {
        $response = $this->postJson('/api/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price', 'stock_quantity']);
    }

    public function testProductSearchFunctionality(): void
    {
        Product::factory()->create(['name' => 'Samsung Phone']);
        Product::factory()->create(['name' => 'iPhone']);
        Product::factory()->create(['name' => 'Samsung TV']);

        $response = $this->getJson('/api/products?search=Samsung');

        $response->assertStatus(200);

        $products = $response->json('data');
        $this->assertCount(2, $products);
    }
}

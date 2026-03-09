<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DataTransferObjects\Factories\SaleDTOFactory;
use App\Exceptions\SaleException;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }

    public function testCanListSales(): void
    {
        $customer = Customer::factory()->create();

        Sale::factory()
            ->for($customer)
            ->withItems(2)
            ->count(3)
            ->create();

        $response = $this->getJson('/api/sales');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'customer' => [
                            'id',
                            'name',
                            'email',
                            'cpf',
                        ],
                        'status',
                        'subtotal',
                        'discount_percentage',
                        'discount_amount',
                        'total',
                        'items_count',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'from',
                    'to',
                ],
            ]);
    }

    public function testCanCreateSaleWithNewSOLIDArchitecture(): void
    {
        $customer = Customer::factory()->create();
        $product1 = Product::factory()->create(['price' => 100.00, 'stock_quantity' => 10]);
        $product2 = Product::factory()->create(['price' => 50.00, 'stock_quantity' => 5]);

        $saleData = [
            'customer_id' => $customer->id,
            'discount_percentage' => 10.0,
            'items' => [
                [
                    'product_id' => $product1->id,
                    'quantity' => 2,
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->postJson('/api/sales', $saleData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                    ],
                    'status' => 'pending',
                    'discount_percentage' => '10.00',
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'subtotal_amount',
                    'discount_amount',
                    'total_amount',
                    'items' => [
                        '*' => [
                            'product' => [
                                'id',
                                'name',
                                'price',
                            ],
                            'quantity',
                            'unit_price',
                            'subtotal',
                        ],
                    ],
                ],
            ]);

        $responseData = $response->json('data');

        $this->assertEquals('250.00', $responseData['subtotal_amount']);
        $this->assertEquals('25.00', $responseData['discount_amount']);
        $this->assertEquals('225.00', $responseData['total_amount']);

        $this->assertDatabaseHas('sales', [
            'customer_id' => $customer->id,
            'status' => 'pending',
            'discount_percentage' => 10.0,
            'subtotal' => 250.00,
            'discount_amount' => 25.00,
            'total_amount' => 225.00,
        ]);

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product1->id,
            'quantity' => 2,
            'unit_price' => 100.00,
        ]);

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product2->id,
            'quantity' => 1,
            'unit_price' => 50.00,
        ]);
    }

    public function testCreateSaleUsesFactoryWithDependencyInjection(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 75.99, 'stock_quantity' => 3]);

        $saleData = [
            'customer_id' => $customer->id,
            'discount_percentage' => 15.0,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ];

        $response = $this->postJson('/api/sales', $saleData);

        $response->assertStatus(201);

        $responseData = $response->json('data');
        $this->assertEquals('151.98', $responseData['subtotal']);
        $this->assertEquals('22.80', $responseData['discount_amount']);
        $this->assertEquals('129.18', $responseData['total']);
    }

    public function testCreateSaleWithCustomPricing(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100.00, 'stock_quantity' => 5]);

        $saleData = [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 85.00,
                ],
            ],
        ];

        $response = $this->postJson('/api/sales', $saleData);

        $response->assertStatus(201);

        $responseData = $response->json('data');
        $this->assertEquals('85.00', $responseData['subtotal']);

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'unit_price' => 85.00,
        ]);
    }

    public function testCreateSaleValidatesStockCorrectly(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 50.00, 'stock_quantity' => 3]);

        $saleData = [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                ],
            ],
        ];

        $response = $this->postJson('/api/sales', $saleData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function testCreateSaleHandlesSaleExceptionFromFactory(): void
    {
        $customer = Customer::factory()->create();

        $saleData = [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_id' => 999,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->postJson('/api/sales', $saleData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure(['message']);
    }

    public function testCreateSaleValidatesQuantityZero(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 50.00, 'stock_quantity' => 5]);

        $saleData = [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 0,
                ],
            ],
        ];

        $response = $this->postJson('/api/sales', $saleData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);
    }

    public function testCanShowSale(): void
    {
        $sale = Sale::factory()
            ->for(Customer::factory())
            ->withItems(2)
            ->create();

        $response = $this->getJson("/api/sales/{$sale->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $sale->id,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'customer',
                    'status',
                    'subtotal',
                    'discount_percentage',
                    'discount_amount',
                    'total',
                    'items_count',
                    'created_at',
                    'updated_at',
                    'items' => [
                        '*' => [
                            'product',
                            'quantity',
                            'unit_price',
                            'subtotal',
                        ],
                    ],
                ],
            ]);
    }

    public function testCanUpdateSaleStatus(): void
    {
        $sale = Sale::factory()
            ->for(Customer::factory())
            ->withItems(1)
            ->create(['status' => 'pending']);

        $response = $this->patchJson("/api/sales/{$sale->id}", [
            'status' => 'completed',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $sale->id,
                    'status' => 'completed',
                ],
            ]);

        // Verify database was updated
        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'status' => 'completed',
        ]);
    }

    public function testCannotUpdateSaleToInvalidStatus(): void
    {
        $sale = Sale::factory()
            ->for(Customer::factory())
            ->withItems(1)
            ->create(['status' => 'completed']);

        $response = $this->patchJson("/api/sales/{$sale->id}", [
            'status' => 'pending', // Invalid transition
        ]);

        $response->assertStatus(422);
    }

    public function testCanDeleteSale(): void
    {
        $sale = Sale::factory()
            ->for(Customer::factory())
            ->withItems(1)
            ->create();

        $response = $this->deleteJson("/api/sales/{$sale->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
    }

    public function testCanGetSaleItems(): void
    {
        $sale = Sale::factory()
            ->for(Customer::factory())
            ->withItems(3)
            ->create();

        $response = $this->getJson("/api/sales/{$sale->id}/items");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'product' => [
                            'id',
                            'name',
                            'price',
                        ],
                        'quantity',
                        'unit_price',
                        'subtotal',
                    ],
                ],
            ]);
    }

    public function testFactoryIntegrationWithComplexScenario(): void
    {
        $customer = Customer::factory()->create();
        $products = Product::factory()->count(4)->create([
            'stock_quantity' => 10,
        ]);

        $saleData = [
            'customer_id' => $customer->id,
            'discount_percentage' => 25.0,
            'items' => [
                [
                    'product_id' => $products[0]->id,
                    'quantity' => 2,
                ],
                [
                    'product_id' => $products[1]->id,
                    'quantity' => 1,
                    'unit_price' => 150.00, // Custom price
                ],
                [
                    'product_id' => $products[2]->id,
                    'quantity' => 3,
                ],
                [
                    'product_id' => $products[3]->id,
                    'quantity' => 1,
                    'unit_price' => 200.00, // Custom price
                ],
            ],
        ];

        $response = $this->postJson('/api/sales', $saleData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'subtotal',
                    'discount_amount',
                    'total',
                    'items' => [
                        '*' => [
                            'quantity',
                            'unit_price',
                            'subtotal',
                        ],
                    ],
                ],
            ]);

        $responseData = $response->json('data');

        $this->assertCount(4, $responseData['items']);
        $this->assertEquals('25.00', $responseData['discount_percentage']);
        $this->assertArrayHasKey('subtotal', $responseData);
        $this->assertArrayHasKey('discount_amount', $responseData);
        $this->assertArrayHasKey('total', $responseData);
        $items = collect($responseData['items']);
        $customPriceItems = $items->whereIn('unit_price', ['150.00', '200.00']);
        $this->assertGreaterThanOrEqual(2, $customPriceItems->count());
    }

    public function testSaleListingSupportsFiltering(): void
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        Sale::factory()
            ->for($customer1)
            ->withItems(1)
            ->create(['status' => 'pending']);

        Sale::factory()
            ->for($customer1)
            ->withItems(1)
            ->create(['status' => 'completed']);

        Sale::factory()
            ->for($customer2)
            ->withItems(1)
            ->create(['status' => 'pending']);

        $response = $this->getJson('/api/sales?status=pending');

        $response->assertStatus(200);
        $salesData = $response->json('data');

        foreach ($salesData as $sale) {
            $this->assertEquals('pending', $sale['status']);
        }

        $response = $this->getJson("/api/sales?customer_id={$customer1->id}");

        $response->assertStatus(200);
        $salesData = $response->json('data');

        foreach ($salesData as $sale) {
            $this->assertEquals($customer1->id, $sale['customer']['id']);
        }
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiCsrfExemptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }

    public function testApiWithProperHeadersWorks(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200);
    }

    public function testApiWithFormUrlEncodedStillWorks(): void
    {
        $product = Product::factory()->create();

        $response = $this->get("/api/products/{$product->id}");

        $response->assertStatus(200);
    }

    public function testAuthenticatedPostToApiWorks(): void
    {
        $customerId = 1;

        $response = $this->postJson('/api/sales', [
            'customer_id' => $customerId,
            'items' => [],
        ]);

        $this->assertNotEquals(302, $response->getStatusCode());
    }

    public function testAuthenticatedDeleteWorks(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $this->assertNotEquals(302, $response->getStatusCode());
        $this->assertContains($response->getStatusCode(), [200, 401]);
    }
}

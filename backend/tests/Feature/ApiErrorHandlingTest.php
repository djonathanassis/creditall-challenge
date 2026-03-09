<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function testRouteNotFoundReturnsJson404(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/rota-inexistente-xyz-123');

        $response->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => [],
            ])
            ->assertHeader('Content-Type', 'application/json');
    }

    public function testUnauthorizedAccessReturns401(): void
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(401)
            ->assertJsonStructure([
                'message',
            ])
            ->assertHeader('Content-Type', 'application/json');
    }

    public function testValidationErrorReturnsJson422(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/products', [
            'name' => '',
            'price' => -10,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => ['name', 'price'],
            ])
            ->assertHeader('Content-Type', 'application/json');
    }

    public function testAllApiErrorsHaveJsonStructure(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/rota-inexistente');

        $response->assertStatus(404)
            ->assertHeader('Content-Type', 'application/json');
    }

    public function testMethodNotAllowedReturns405(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/health');

        $response->assertStatus(405)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['method'],
            ])
            ->assertHeader('Content-Type', 'application/json');
    }
}

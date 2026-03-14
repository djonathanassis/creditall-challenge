<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }

    public function testCanListCustomers(): void
    {
        Customer::factory()->count(5)->create();

        $response = $this->getJson('/api/customers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['*' => ['id', 'name', 'email', 'cpf', 'phone']],
                'meta',
            ]);
    }

    public function testCanCreateCustomer(): void
    {
        $data = [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'cpf' => '11144477735',
            'phone' => '(11) 99999-9999',
        ];

        $response = $this->postJson('/api/customers', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('customers', [
            'email' => 'joao@example.com',
            'phone' => '11999999999',
        ]);
    }

    public function testCanShowCustomer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->getJson("/api/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $customer->id]);
    }

    public function testCanUpdateCustomer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->putJson("/api/customers/{$customer->id}", [
            'name' => 'Nome Atualizado',
            'phone' => '(11) 98888-7777',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Nome Atualizado']);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'phone' => '11988887777',
        ]);
    }

    public function testCanDeleteCustomer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->deleteJson("/api/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['success' => true]);

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function testCustomerValidation(): void
    {
        $response = $this->postJson('/api/customers', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'cpf', 'phone']);
    }

    public function testCustomerPhoneMustBeValid(): void
    {
        $response = $this->postJson('/api/customers', [
            'name' => 'Cliente Teste',
            'email' => 'cliente@teste.com',
            'cpf' => '11144477735',
            'phone' => '12345',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function testCustomerSearch(): void
    {
        Customer::factory()->create(['name' => 'João Silva']);
        Customer::factory()->create(['name' => 'Maria Santos']);
        Customer::factory()->create(['name' => 'João Pedro']);

        $response = $this->getJson('/api/customers?search=João');

        $response->assertStatus(200);
        $customers = $response->json('data');
        $this->assertCount(2, $customers);
    }
}

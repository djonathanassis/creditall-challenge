<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuthService();
    }

    public function testLoginWithValidCredentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $result = $this->service->login('test@example.com', 'password123');

        $this->assertNotNull($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals($user->id, $result['user']->id);
        $this->assertIsString($result['token']);
    }

    public function testLoginWithInvalidEmail(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $result = $this->service->login('wrong@example.com', 'password123');

        $this->assertNull($result);
    }

    public function testLoginWithInvalidPassword(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $result = $this->service->login('test@example.com', 'wrongpassword');

        $this->assertNull($result);
    }

    public function testLogoutThrowsExceptionWhenNoCurrentToken(): void
    {
        $user = User::factory()->create();

        $this->expectException(\Error::class);

        $this->service->logout($user);
    }

    public function testGetAuthenticatedUserReturnsCurrentUser(): void
    {
        $user = User::factory()->create();

        Auth::login($user);

        $result = $this->service->getAuthenticatedUser();

        $this->assertEquals($user->id, $result->id);
    }

    public function testGetAuthenticatedUserReturnsNullWhenNotAuthenticated(): void
    {
        $result = $this->service->getAuthenticatedUser();

        $this->assertNull($result);
    }

    public function testCreateUser(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $user = $this->service->createUser($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function testCreateUserThrowsExceptionWhenEmailExists(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $this->service->createUser([
            'name' => 'John',
            'email' => 'existing@example.com',
            'password' => 'password123',
        ]);
    }
}

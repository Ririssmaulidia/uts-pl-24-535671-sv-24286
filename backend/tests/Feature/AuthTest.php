<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_for_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'rahasia123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'token', 'token_type', 'user' => ['id', 'name', 'email']]);
    }

    public function test_login_with_wrong_password_returns_401(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'salah-total',
        ])->assertUnauthorized()
            ->assertJson(['message' => 'Email atau password salah.']);
    }

    public function test_login_without_fields_returns_422(): void
    {
        $this->postJson('/api/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_logout_deletes_the_token(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'rahasia123',
        ])->json('token');

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_protected_endpoint_without_token_returns_401(): void
    {
        $this->getJson('/api/expenses')->assertUnauthorized();
        $this->postJson('/api/logout')->assertUnauthorized();
    }
}

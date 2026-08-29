<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PassportCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_require_an_access_token(): void
    {
        $this->getJson('/api/v1/products')->assertUnauthorized();
    }

    public function test_user_can_login_and_execute_the_complete_crud(): void
    {
        Artisan::call('passport:client', [
            '--personal' => true,
            '--name' => 'Testing Personal Access Client',
            '--no-interaction' => true,
        ]);

        User::factory()->create([
            'email' => 'demo@example.com',
            'password' => 'password123',
        ]);

        $loginResponse = $this->postJson('/api/v1/login', [
            'email' => 'demo@example.com',
            'password' => 'password123',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['access_token']);

        $accessToken = $loginResponse->json('access_token');

        $createResponse = $this->withToken($accessToken)->postJson('/api/v1/products', [
            'name' => 'Teclado mecanico',
            'description' => 'Producto creado desde la prueba.',
            'price' => 75.50,
            'stock' => 10,
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.name', 'Teclado mecanico');

        $productId = $createResponse->json('data.id');

        $this->withToken($accessToken)
            ->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Teclado mecanico']);

        $this->withToken($accessToken)
            ->putJson("/api/v1/products/{$productId}", [
                'price' => 80,
                'stock' => 8,
            ])
            ->assertOk()
            ->assertJsonPath('data.stock', 8);

        $this->withToken($accessToken)
            ->deleteJson("/api/v1/products/{$productId}")
            ->assertOk();

        $this->assertDatabaseMissing('products', ['id' => $productId]);
    }
}

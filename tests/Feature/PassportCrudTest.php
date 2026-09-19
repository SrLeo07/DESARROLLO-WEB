<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
use Tests\TestCase;

class PassportCrudTest extends TestCase
{
    use RefreshDatabase;

    private string $clientId;

    private string $clientSecret;

    protected function setUp(): void
    {
        parent::setUp();

        $client = app(ClientRepository::class)->createPasswordGrantClient(
            'Cliente Password Grant de pruebas',
            'users',
            true,
        );

        $this->clientId = (string) $client->getKey();
        $this->clientSecret = (string) $client->plainSecret;

        config()->set('passport.password_client_id', $this->clientId);
        config()->set('passport.password_client_secret', $this->clientSecret);
    }

    public function test_protected_routes_require_authentication(): void
    {
        $this->getJson('/api/productos')->assertUnauthorized();
        $this->getJson('/api/me')->assertUnauthorized();
        $this->postJson('/api/logout')->assertUnauthorized();
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $this->createUser();

        $this->postJson('/api/login', [
            'email' => 'demo@example.com',
            'password' => 'incorrecta',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Credenciales incorrectas.');
    }

    public function test_login_uses_the_default_scope_and_me_returns_the_user(): void
    {
        $user = $this->createUser();

        $login = $this->login();

        $login->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('expires_in', 3600)
            ->assertJsonStructure(['access_token', 'refresh_token']);

        $accessToken = Token::query()->sole();

        $this->assertSame(['productos.read'], $accessToken->scopes);
        $this->assertTrue($accessToken->expires_at->isBetween(
            now()->addMinutes(59),
            now()->addMinutes(61),
        ));

        $this->withToken($login->json('access_token'))
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', 'demo@example.com');
    }

    public function test_login_validates_requested_scopes(): void
    {
        $this->createUser();

        $this->postJson('/api/login', [
            'email' => 'demo@example.com',
            'password' => 'password123',
            'scopes' => ['productos.read', 'scope.inexistente'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('scopes.1');
    }

    public function test_read_only_scope_allows_get_and_denies_writes(): void
    {
        $this->createUser();
        $product = Product::factory()->create();
        $token = $this->login(['productos.read'])->json('access_token');

        $this->withToken($token)
            ->getJson('/api/productos')
            ->assertOk()
            ->assertJsonFragment(['id' => $product->id]);

        $this->withToken($token)
            ->postJson('/api/productos', $this->productPayload())
            ->assertForbidden();

        $this->withToken($token)
            ->deleteJson("/api/productos/{$product->id}")
            ->assertForbidden();
    }

    public function test_correct_scopes_allow_the_complete_products_crud(): void
    {
        $this->createUser();
        $token = $this->login([
            'productos.read',
            'productos.write',
            'productos.delete',
        ])->json('access_token');

        $create = $this->withToken($token)
            ->postJson('/api/productos', $this->productPayload())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Teclado mecanico');

        $productId = $create->json('data.id');

        $this->withToken($token)
            ->getJson('/api/productos')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Teclado mecanico']);

        $this->withToken($token)
            ->getJson("/api/productos/{$productId}")
            ->assertOk()
            ->assertJsonPath('data.id', $productId);

        $this->withToken($token)
            ->patchJson("/api/productos/{$productId}", [
                'price' => 80,
                'stock' => 8,
            ])->assertOk()
            ->assertJsonPath('data.stock', 8);

        $this->withToken($token)
            ->deleteJson("/api/productos/{$productId}")
            ->assertOk();

        $this->assertDatabaseMissing('products', ['id' => $productId]);
    }

    public function test_refresh_token_issues_a_new_token_and_revokes_the_previous_refresh_token(): void
    {
        $this->createUser();
        $login = $this->login(['productos.read']);
        $oldRefreshToken = RefreshToken::query()->sole();

        $refresh = $this->post('/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $login->json('refresh_token'),
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => 'productos.read',
        ], ['Accept' => 'application/json']);

        $refresh->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['access_token', 'refresh_token']);

        $this->assertTrue($oldRefreshToken->refresh()->revoked);

        $this->withToken($refresh->json('access_token'))
            ->getJson('/api/productos')
            ->assertOk();
    }

    public function test_logout_revokes_access_and_refresh_tokens(): void
    {
        $this->createUser();
        $login = $this->login(['productos.read']);
        $accessToken = Token::query()->sole();
        $refreshToken = $accessToken->refreshToken()->sole();

        $this->withToken($login->json('access_token'))
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertTrue($accessToken->refresh()->revoked);
        $this->assertTrue($refreshToken->refresh()->revoked);

        Auth::forgetGuards();

        $this->withToken($login->json('access_token'))
            ->getJson('/api/me')
            ->assertUnauthorized();

        $this->withoutToken()->post('/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $login->json('refresh_token'),
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => 'productos.read',
        ], ['Accept' => 'application/json'])
            ->assertBadRequest()
            ->assertJsonPath('error', 'invalid_grant');
    }

    private function createUser(): User
    {
        return User::factory()->create([
            'email' => 'demo@example.com',
            'password' => 'password123',
        ]);
    }

    /**
     * @param  list<string>|null  $scopes
     */
    private function login(?array $scopes = null): TestResponse
    {
        $payload = [
            'email' => 'demo@example.com',
            'password' => 'password123',
        ];

        if ($scopes !== null) {
            $payload['scopes'] = $scopes;
        }

        return $this->postJson('/api/login', $payload);
    }

    /**
     * @return array{name: string, description: string, price: float, stock: int}
     */
    private function productPayload(): array
    {
        return [
            'name' => 'Teclado mecanico',
            'description' => 'Producto creado desde la prueba.',
            'price' => 75.50,
            'stock' => 10,
        ];
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionTokenBridgeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email' => 'sessiontest@example.com',
        ]);
    }

    public function test_web_login_generates_and_stores_sanctum_token_in_session(): void
    {
        $response = $this->post('/login', [
            'email'    => 'sessiontest@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        // Verify api_token exists in session
        $this->assertTrue(session()->has('api_token'));
        $token = session('api_token');
        $this->assertNotEmpty($token);

        // Verify the token works against protected API endpoints
        $apiResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user');

        $apiResponse->assertOk()
            ->assertJsonPath('user.email', 'sessiontest@example.com');
    }

    public function test_api_auth_middleware_falls_back_to_active_web_session(): void
    {
        // Log in via web guard without Bearer token header
        $response = $this->actingAs($this->user, 'web')
            ->getJson('/api/user');

        $response->assertOk()
            ->assertJsonPath('user.email', 'sessiontest@example.com');
    }

    public function test_api_auth_middleware_handles_bearer_null_string_gracefully_with_session(): void
    {
        // When frontend accidentally sends "Bearer null" but user has active web session
        $response = $this->actingAs($this->user, 'web')
            ->withHeader('Authorization', 'Bearer null')
            ->getJson('/api/user');

        $response->assertOk()
            ->assertJsonPath('user.email', 'sessiontest@example.com');
    }

    public function test_api_auth_middleware_rejects_unauthenticated_request_with_bearer_null(): void
    {
        // When unauthenticated guest sends "Bearer null"
        $response = $this->withHeader('Authorization', 'Bearer null')
            ->getJson('/api/user');

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated. Bearer token required.');
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\TransientToken;
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

    public function test_web_login_authenticates_session_without_exposing_api_token(): void
    {
        $response = $this->post('/login', [
            'email'    => 'sessiontest@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        // Verify api_token is NOT placed in the session (security fix)
        $this->assertFalse(session()->has('api_token'));

        // Verify no personal_access_tokens were created in database for standard web login
        $this->assertEquals(0, $this->user->tokens()->count());

        // Verify the user can access protected API endpoints seamlessly via stateful cookie session
        $apiResponse = $this->getJson('/api/user');

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

    public function test_api_auth_middleware_attaches_transient_token_to_web_session_user(): void
    {
        // When authenticated via web session, currentAccessToken() must return a TransientToken
        $response = $this->actingAs($this->user, 'web')
            ->getJson('/api/user');

        $response->assertOk();
        $this->assertInstanceOf(TransientToken::class, auth()->user()->currentAccessToken());
    }

    public function test_logout_via_api_succeeds_without_500_error_for_session_user(): void
    {
        // A web session user (with TransientToken) calling POST /api/logout
        // previously threw a 500 error because ->delete() was called on TransientToken or null.
        $response = $this->actingAs($this->user, 'web')
            ->postJson('/api/logout');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully.',
            ]);
    }

    public function test_logout_via_api_succeeds_and_deletes_token_for_bearer_token_user(): void
    {
        // Issue an API token
        $plainToken = $this->user->createToken('test-device')->plainTextToken;
        $this->assertEquals(1, $this->user->tokens()->count());

        $response = $this->withHeader('Authorization', 'Bearer ' . $plainToken)
            ->postJson('/api/logout');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully.',
            ]);

        // Token should be revoked from database
        $this->assertEquals(0, $this->user->fresh()->tokens()->count());
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

    public function test_blade_layout_does_not_generate_tokens_or_expose_them_in_html(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->get('/account');

        $response->assertOk();

        // Must not generate tokens
        $this->assertEquals(0, $this->user->tokens()->count());

        // Must not expose SESSION_TOKEN or token creation in HTML
        $content = $response->getContent();
        $this->assertStringNotContainsString('window.SESSION_TOKEN', $content);
        $this->assertStringNotContainsString('createToken', $content);
    }
}

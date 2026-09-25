<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FrontendApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private Category $category;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'testuser@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->token = Crypt::encryptString("{$this->user->id}|" . time());

        $this->category = Category::create(['name' => 'Computers']);

        $this->product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Laptop Pro',
            'description' => 'Powerful dev laptop',
            'price' => 1299.99,
            'stock' => 10,
        ]);
    }

    public function test_frontend_pages_render_successfully(): void
    {
        $routes = [
            '/',
            '/login',
            '/register',
            '/products',
            "/products/{$this->product->id}",
            '/categories',
            '/cart',
            '/wishlist',
            '/addresses',
            '/profile',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            if ($route === '/profile') {
                $response->assertRedirect('/account#profile');
            } else {
                $response->assertOk();
            }
        }
    }

    public function test_auth_registration_and_login(): void
    {
        // Registration
        $regResponse = $this->postJson('/api/register', [
            'name' => 'Alice Dev',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $regResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['token', 'user']);

        // Login
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'alice@example.com',
            'password' => 'secret123',
        ]);

        $loginResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_auth_profile_and_password_update(): void
    {
        // Profile view
        $res = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/user');
        $res->assertOk()->assertJsonPath('user.email', 'testuser@example.com');

        // Update profile
        $updateRes = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson('/api/profile', [
                'name' => 'Updated Name',
                'email' => 'newemail@example.com',
            ]);
        $updateRes->assertOk()->assertJsonPath('user.name', 'Updated Name');

        // Update password
        $passRes = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson('/api/password', [
                'current_password' => 'password123',
                'password' => 'newpassword456',
                'password_confirmation' => 'newpassword456',
            ]);
        $passRes->assertOk()->assertJsonPath('success', true);
    }

    public function test_categories_api(): void
    {
        $res = $this->getJson('/api/categories');
        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        // Customer cannot create category
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/categories', ['name' => 'Smartphones'])
            ->assertForbidden();

        // Admin can create category
        $admin = User::factory()->create(['role' => 'admin']);
        $adminToken = Crypt::encryptString("{$admin->id}|" . time());

        $createRes = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->postJson('/api/categories', ['name' => 'Smartphones']);
        $createRes->assertCreated();
    }

    public function test_cart_operations(): void
    {
        // Add to cart
        $addRes = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/cart/items', [
                'product_id' => $this->product->id,
                'quantity' => 2,
            ]);
        $addRes->assertOk()->assertJsonPath('success', true);

        // Get cart
        $cartRes = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/cart');
        $cartRes->assertOk()
            ->assertJsonPath('data.total_items', 2)
            ->assertJsonPath('data.subtotal', 2599.98);

        $itemId = $cartRes->json('data.items.0.id');

        // Update quantity
        $updateRes = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/cart/items/{$itemId}", [
                'quantity' => 3,
            ]);
        $updateRes->assertOk()->assertJsonPath('data.quantity', 3);

        // Stock limit violation
        $overflowRes = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/cart/items/{$itemId}", [
                'quantity' => 9999,
            ]);
        $overflowRes->assertStatus(422);

        // Clear cart
        $clearRes = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson('/api/cart');
        $clearRes->assertOk();
    }

    public function test_wishlist_operations(): void
    {
        // Add to wishlist
        $addRes = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/wishlist/items', [
                'product_id' => $this->product->id,
            ]);
        $addRes->assertOk();

        // Get wishlist
        $getRes = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/wishlist');
        $getRes->assertOk()->assertJsonPath('data.total_items', 1);

        $itemId = $getRes->json('data.items.0.id');

        // Delete from wishlist
        $delRes = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/wishlist/items/{$itemId}");
        $delRes->assertOk();
    }

    public function test_addresses_operations(): void
    {
        // Create address
        $createRes = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/addresses', [
                'name' => 'John Doe',
                'phone' => '1234567890',
                'address_line1' => '123 Test St',
                'city' => 'Metropolis',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'USA',
                'is_default' => true,
            ]);
        $createRes->assertCreated()->assertJsonPath('data.is_default', true);

        $addrId = $createRes->json('data.id');

        // Get addresses
        $listRes = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/addresses');
        $listRes->assertOk()->assertJsonCount(1, 'data');

        // Delete address
        $delRes = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/addresses/{$addrId}");
        $delRes->assertOk();
    }
}

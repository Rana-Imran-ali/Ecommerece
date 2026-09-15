<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class CouponSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;
    protected Address $address;
    protected Category $category;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'customer@example.com',
            'role' => 'user',
        ]);

        $this->token = Crypt::encryptString("{$this->user->id}|" . time());

        $this->category = Category::create(['name' => 'Electronics']);

        $this->address = Address::create([
            'user_id' => $this->user->id,
            'name' => 'John Doe',
            'phone' => '1234567890',
            'address_line1' => '123 Main St',
            'city' => 'Metropolis',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'USA',
        ]);

        $this->product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Test Widget',
            'slug' => 'test-widget',
            'description' => 'A widget for testing',
            'price' => 50.00,
            'stock' => 100,
            'is_active' => true,
        ]);
    }

    public function test_rejects_expired_coupon_during_validation(): void
    {
        $coupon = Coupon::create([
            'code' => 'EXPIRED10',
            'discount_type' => 'percent',
            'discount_percent' => 10,
            'expires_at' => Carbon::now()->subDay(),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/coupons/validate', [
            'code' => 'EXPIRED10',
            'order_amount' => 100.00,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', "Coupon 'EXPIRED10' has expired.");
    }

    public function test_accepts_valid_unexpired_coupon(): void
    {
        $coupon = Coupon::create([
            'code' => 'ACTIVE10',
            'discount_type' => 'percent',
            'discount_percent' => 10,
            'expires_at' => Carbon::now()->addDays(5),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/coupons/validate', [
            'code' => 'ACTIVE10',
            'order_amount' => 100.00,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.discount_amount', 10)
            ->assertJsonPath('data.final_amount', 90);
    }

    public function test_supports_fixed_amount_discount(): void
    {
        $coupon = Coupon::create([
            'code' => 'FLAT15',
            'discount_type' => 'fixed',
            'discount_amount' => 15.00,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/coupons/validate', [
            'code' => 'FLAT15',
            'order_amount' => 50.00,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.discount_type', 'fixed')
            ->assertJsonPath('data.discount_amount', 15)
            ->assertJsonPath('data.final_amount', 35);
    }

    public function test_discount_calculation_never_exceeds_order_total(): void
    {
        // Fixed discount greater than order amount ($60 discount on $40 order)
        $coupon = Coupon::create([
            'code' => 'BIG60',
            'discount_type' => 'fixed',
            'discount_amount' => 60.00,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/coupons/validate', [
            'code' => 'BIG60',
            'order_amount' => 40.00,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.discount_amount', 40)
            ->assertJsonPath('data.final_amount', 0);
    }

    public function test_enforces_maximum_global_usage_limit_in_validation(): void
    {
        $coupon = Coupon::create([
            'code' => 'LIMITED2',
            'discount_type' => 'percent',
            'discount_percent' => 20,
            'max_uses' => 2,
            'is_active' => true,
        ]);

        // First 2 uses by other users with real orders
        $otherUser1 = User::factory()->create();
        $otherUser2 = User::factory()->create();

        $order1 = Order::create(['user_id' => $otherUser1->id, 'address_id' => $this->address->id, 'status' => 'pending', 'total_amount' => 100]);
        $order2 = Order::create(['user_id' => $otherUser2->id, 'address_id' => $this->address->id, 'status' => 'pending', 'total_amount' => 100]);

        CouponUsage::create(['coupon_id' => $coupon->id, 'user_id' => $otherUser1->id, 'order_id' => $order1->id]);
        CouponUsage::create(['coupon_id' => $coupon->id, 'user_id' => $otherUser2->id, 'order_id' => $order2->id]);

        // Attempt validation when usages reached max_uses
        $response = $this->postJson('/api/coupons/validate', [
            'code' => 'LIMITED2',
            'order_amount' => 100.00,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', "Coupon 'LIMITED2' has reached its maximum usage limit.");
    }

    public function test_rejects_expired_coupon_during_order_placement(): void
    {
        $coupon = Coupon::create([
            'code' => 'EXPIREDORDER',
            'discount_type' => 'fixed',
            'discount_amount' => 10.00,
            'expires_at' => Carbon::now()->subDay(),
            'is_active' => true,
        ]);

        $cart = Cart::create(['user_id' => $this->user->id]);
        $cart->items()->create(['product_id' => $this->product->id, 'quantity' => 2]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/orders', [
                'address_id' => $this->address->id,
                'payment_method' => 'cod',
                'coupon_code' => 'EXPIREDORDER',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', "Coupon 'EXPIREDORDER' has expired.");
    }

    public function test_rejects_fully_used_coupon_during_order_placement(): void
    {
        $coupon = Coupon::create([
            'code' => 'MAXEDOUT',
            'discount_type' => 'fixed',
            'discount_amount' => 10.00,
            'max_uses' => 1,
            'is_active' => true,
        ]);

        $otherUser = User::factory()->create();
        $order = Order::create(['user_id' => $otherUser->id, 'address_id' => $this->address->id, 'status' => 'pending', 'total_amount' => 100]);
        CouponUsage::create(['coupon_id' => $coupon->id, 'user_id' => $otherUser->id, 'order_id' => $order->id]);

        $cart = Cart::create(['user_id' => $this->user->id]);
        $cart->items()->create(['product_id' => $this->product->id, 'quantity' => 2]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/orders', [
                'address_id' => $this->address->id,
                'payment_method' => 'cod',
                'coupon_code' => 'MAXEDOUT',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', "Coupon 'MAXEDOUT' has reached its maximum usage limit.");
    }

    public function test_order_placement_with_fixed_discount_calculates_total_correctly(): void
    {
        $coupon = Coupon::create([
            'code' => 'FLAT20',
            'discount_type' => 'fixed',
            'discount_amount' => 20.00,
            'is_active' => true,
        ]);

        $cart = Cart::create(['user_id' => $this->user->id]);
        $cart->items()->create(['product_id' => $this->product->id, 'quantity' => 2]); // 2 * $50 = $100

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/orders', [
                'address_id' => $this->address->id,
                'payment_method' => 'cod',
                'coupon_code' => 'FLAT20',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $order = $response->json('data');
        // Total should be $100 - $20 = $80
        $this->assertEquals(80.00, (float) $order['total_amount']);

        // CouponUsage recorded
        $this->assertDatabaseHas('coupon_usages', [
            'coupon_id' => $coupon->id,
            'user_id' => $this->user->id,
            'order_id' => $order['id'],
        ]);
    }

    public function test_admin_can_create_fixed_and_percent_coupons(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create percent coupon
        $resPercent = $this->actingAs($admin)->post('/admin/coupons', [
            'code' => 'ADMINPERC',
            'discount_type' => 'percent',
            'discount_percent' => 25,
            'max_discount' => 50,
            'min_order_amount' => 100,
            'max_uses' => 100,
            'expires_at' => Carbon::now()->addMonth()->format('Y-m-d H:i:s'),
            'is_active' => 1,
        ]);

        $resPercent->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseHas('coupons', [
            'code' => 'ADMINPERC',
            'discount_type' => 'percent',
            'discount_percent' => 25.00,
            'max_uses' => 100,
        ]);

        // Create fixed coupon
        $resFixed = $this->actingAs($admin)->post('/admin/coupons', [
            'code' => 'ADMINFIXED',
            'discount_type' => 'fixed',
            'discount_amount' => 30.00,
            'max_uses' => 50,
            'is_active' => 1,
        ]);

        $resFixed->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseHas('coupons', [
            'code' => 'ADMINFIXED',
            'discount_type' => 'fixed',
            'discount_amount' => 30.00,
            'max_uses' => 50,
        ]);
    }
}

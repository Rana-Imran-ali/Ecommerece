<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class OrderAndReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $user1;
    private User $user2;
    private string $token1;
    private string $token2;
    private Category $category;
    private Product $product;
    private Address $address1;
    private Address $address2;
    private Coupon $coupon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user1 = User::factory()->create(['name' => 'Alice']);
        $this->token1 = Crypt::encryptString("{$this->user1->id}|" . time());

        $this->user2 = User::factory()->create(['name' => 'Bob']);
        $this->token2 = Crypt::encryptString("{$this->user2->id}|" . time());

        $this->category = Category::create(['name' => 'Electronics']);

        $this->product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Flagship Phone',
            'price' => 100.00,
            'stock' => 20,
        ]);

        $this->address1 = Address::create([
            'user_id' => $this->user1->id,
            'name' => 'Alice Address',
            'phone' => '1111111111',
            'address_line1' => '1st Street',
            'city' => 'City A',
            'state' => 'State A',
            'postal_code' => '11111',
            'country' => 'Country A',
            'is_default' => true,
        ]);

        $this->address2 = Address::create([
            'user_id' => $this->user2->id,
            'name' => 'Bob Address',
            'phone' => '2222222222',
            'address_line1' => '2nd Street',
            'city' => 'City B',
            'state' => 'State B',
            'postal_code' => '22222',
            'country' => 'Country B',
            'is_default' => true,
        ]);

        $this->coupon = Coupon::create([
            'code' => 'SAVE10',
            'discount_percent' => 10.00,
            'max_discount' => 50.00,
            'min_order_amount' => 50.00,
            'is_active' => true,
        ]);
    }

    public function test_checkout_places_order_decrements_stock_and_creates_payment(): void
    {
        // 1. Put 2 items in User1's cart ($200 subtotal)
        $cart = Cart::create(['user_id' => $this->user1->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        // Initial stock: 20
        $this->assertEquals(20, $this->product->fresh()->stock);

        // 2. Checkout with coupon SAVE10 (10% of $200 = $20 off -> $180 total)
        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->postJson('/api/orders', [
                'address_id' => $this->address1->id,
                'payment_method' => 'cod',
                'coupon_code' => 'SAVE10',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_amount', '180.00')
            ->assertJsonPath('data.status', 'pending');

        $orderId = $response->json('data.id');

        // 3. Verify stock decremented from 20 to 18
        $this->assertEquals(18, $this->product->fresh()->stock);

        // 4. Verify payment created
        $this->assertDatabaseHas('payments', [
            'order_id' => $orderId,
            'payment_method' => 'cod',
            'amount' => 180.00,
            'status' => 'pending',
        ]);

        // 5. Verify coupon usage recorded
        $this->assertDatabaseHas('coupon_usages', [
            'coupon_id' => $this->coupon->id,
            'user_id' => $this->user1->id,
            'order_id' => $orderId,
        ]);

        // 6. Verify cart items cleared
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_checkout_rejects_card_without_payment_intent(): void
    {
        $cart = Cart::create(['user_id' => $this->user1->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $this->product->id,
            'quantity'   => 1,
        ]);

        // Card payment without a stripe_payment_intent_id must fail validation
        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->postJson('/api/orders', [
                'address_id'     => $this->address1->id,
                'payment_method' => 'card',
                // stripe_payment_intent_id intentionally omitted
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['stripe_payment_intent_id']);

    }

    public function test_checkout_rejects_duplicate_coupon_usage(): void
    {
        // Simulate that user1 already redeemed this coupon
        $prevOrder = Order::create([
            'user_id' => $this->user1->id,
            'address_id' => $this->address1->id,
            'status' => 'completed',
            'total_amount' => 50.00,
        ]);

        \App\Models\CouponUsage::create([
            'coupon_id' => $this->coupon->id,
            'user_id' => $this->user1->id,
            'order_id' => $prevOrder->id,
        ]);

        $cart = Cart::create(['user_id' => $this->user1->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->postJson('/api/orders', [
                'address_id' => $this->address1->id,
                'payment_method' => 'cod',
                'coupon_code' => $this->coupon->code,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_checkout_rejects_unowned_address(): void
    {
        $cart = Cart::create(['user_id' => $this->user1->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        // User1 attempts to use User2's address
        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->postJson('/api/orders', [
                'address_id' => $this->address2->id,
                'payment_method' => 'cod',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_user_cannot_view_or_cancel_another_users_order(): void
    {
        $order = Order::create([
            'user_id' => $this->user1->id,
            'address_id' => $this->address1->id,
            'status' => 'pending',
            'total_amount' => 100.00,
        ]);

        // User2 tries to view User1's order
        $viewResponse = $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->getJson("/api/orders/{$order->id}");
        $viewResponse->assertStatus(404);

        // User2 tries to cancel User1's order
        $cancelResponse = $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->patchJson("/api/orders/{$order->id}/cancel");
        $cancelResponse->assertStatus(404);
    }

    public function test_order_cancellation_restores_stock(): void
    {
        // Decrement stock initially to simulate active purchase
        $this->product->update(['stock' => 15]);

        $order = Order::create([
            'user_id' => $this->user1->id,
            'address_id' => $this->address1->id,
            'status' => 'pending',
            'total_amount' => 200.00,
        ]);

        $order->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 5,
            'price' => 40.00,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->patchJson("/api/orders/{$order->id}/cancel");

        $response->assertOk();
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);

        // Stock restored from 15 to 20
        $this->assertEquals(20, $this->product->fresh()->stock);
    }

    public function test_product_reviews_creation_and_listing(): void
    {
        // 1. Post a review
        $postRes = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->postJson("/api/products/{$this->product->id}/reviews", [
                'rating' => 5,
                'comment' => 'Incredible product, works as advertised!',
            ]);

        $postRes->assertCreated()
            ->assertJsonPath('data.rating', 5);

        // 2. View product reviews
        $listRes = $this->getJson("/api/products/{$this->product->id}/reviews");
        $listRes->assertOk()
            ->assertJsonPath('data.total_reviews', 1)
            ->assertJsonPath('data.average_rating', 5)
            ->assertJsonPath('data.reviews.0.user.name', 'Alice');
    }

    public function test_user_cannot_delete_another_users_review(): void
    {
        $review = Review::create([
            'user_id' => $this->user1->id,
            'product_id' => $this->product->id,
            'rating' => 4,
            'comment' => 'Solid product',
        ]);

        // User2 attempts to delete User1's review
        $delRes = $this->withHeader('Authorization', "Bearer {$this->token2}")
            ->deleteJson("/api/reviews/{$review->id}");

        $delRes->assertStatus(403);
    }

    public function test_coupon_validation_api(): void
    {
        // Valid coupon above min_order_amount
        $res = $this->postJson('/api/coupons/validate', [
            'code' => 'SAVE10',
            'order_amount' => 100.00,
        ]);

        $res->assertOk()
            ->assertJsonPath('data.discount_amount', 10)
            ->assertJsonPath('data.final_amount', 90);

        // Subtotal below minimum order amount ($50)
        $invalidRes = $this->postJson('/api/coupons/validate', [
            'code' => 'SAVE10',
            'order_amount' => 30.00,
        ]);

        $invalidRes->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_checkout_rejects_replayed_stripe_payment_intent_id(): void
    {
        $prevOrder = Order::create([
            'user_id'      => $this->user1->id,
            'address_id'   => $this->address1->id,
            'status'       => 'completed',
            'total_amount' => 100.00,
        ]);

        // Existing payment with this intent ID
        \App\Models\Payment::create([
            'order_id'                 => $prevOrder->id,
            'payment_method'           => 'card',
            'amount'                   => 100.00,
            'status'                   => 'completed',
            'stripe_payment_intent_id' => 'pi_test_replayed_intent_123',
        ]);

        $cart = Cart::create(['user_id' => $this->user1->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $this->product->id,
            'quantity'   => 1,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->postJson('/api/orders', [
                'address_id'               => $this->address1->id,
                'payment_method'           => 'card',
                'stripe_payment_intent_id' => 'pi_test_replayed_intent_123',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'This payment has already been processed for an existing order.');
    }

    public function test_order_cancel_restores_coupon_usage(): void
    {
        $cart = Cart::create(['user_id' => $this->user1->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $this->product->id,
            'quantity'   => 1,
        ]);

        $res = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->postJson('/api/orders', [
                'address_id'     => $this->address1->id,
                'payment_method' => 'cod',
                'coupon_code'    => 'SAVE10',
            ]);

        $res->assertStatus(201);
        $orderId = $res->json('data.id');

        $this->assertDatabaseHas('coupon_usages', [
            'order_id' => $orderId,
            'user_id'  => $this->user1->id,
        ]);

        // Cancel the order
        $cancelRes = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->patchJson("/api/orders/{$orderId}/cancel");

        $cancelRes->assertOk();

        // Coupon usage must be deleted / restored
        $this->assertDatabaseMissing('coupon_usages', [
            'order_id' => $orderId,
        ]);
    }

    public function test_create_payment_intent_rejects_insufficient_stock(): void
    {
        $this->product->update(['stock' => 1]);

        $cart = Cart::create(['user_id' => $this->user1->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $this->product->id,
            'quantity'   => 5, // exceeds stock of 1
        ]);

        $res = $this->withHeader('Authorization', "Bearer {$this->token1}")
            ->postJson('/api/stripe/payment-intent', [
                'address_id' => $this->address1->id,
            ]);

        $res->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertStringContainsString('Insufficient stock', $res->json('message'));
    }
}

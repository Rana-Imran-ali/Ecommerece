<?php

namespace Tests\Feature;

use App\Http\Controllers\StripeController;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeWebhookRaceConditionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private Address $address;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Charlie']);
        $this->token = $this->user->createToken('test-device')->plainTextToken;

        $category = Category::create(['name' => 'Electronics']);
        $this->product = Product::create([
            'category_id' => $category->id,
            'name'        => 'Wireless Headphones',
            'price'       => 100.00,
            'stock'       => 20,
        ]);

        $this->address = Address::create([
            'user_id'       => $this->user->id,
            'name'          => 'Charlie Home',
            'phone'         => '1234567890',
            'address_line1' => '456 Elm St',
            'city'          => 'Springfield',
            'state'         => 'IL',
            'postal_code'   => '62701',
            'country'       => 'USA',
            'is_default'    => true,
        ]);
    }

    public function test_client_checkout_returns_order_when_webhook_already_fulfilled_it(): void
    {
        $intentId = 'pi_test_webhook_first_' . uniqid();

        // 1. Simulate webhook fulfillment first:
        // Order created, Payment created, cart is empty
        $order = Order::create([
            'user_id'                => $this->user->id,
            'customer_email'         => $this->user->email,
            'address_id'             => $this->address->id,
            'shipping_name'          => $this->address->name,
            'shipping_phone'         => $this->address->phone,
            'shipping_address_line1' => $this->address->address_line1,
            'shipping_city'          => $this->address->city,
            'shipping_state'         => $this->address->state,
            'shipping_postal_code'   => $this->address->postal_code,
            'shipping_country'       => $this->address->country,
            'status'                 => 'processing',
            'total_amount'           => 100.00,
        ]);

        Payment::create([
            'order_id'                 => $order->id,
            'payment_method'           => 'card',
            'amount'                   => 100.00,
            'status'                   => 'completed',
            'stripe_payment_intent_id' => $intentId,
            'transaction_reference'    => $intentId,
        ]);

        // Cart is empty (cleared by webhook)
        Cart::create(['user_id' => $this->user->id]);

        // 2. Client arrives milliseconds later and posts to /api/orders
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/orders', [
                'address_id'               => $this->address->id,
                'payment_method'           => 'card',
                'stripe_payment_intent_id' => $intentId,
            ]);

        // Must succeed with 200 and return the existing order
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('message', 'Order already processed.');
    }

    public function test_webhook_idempotently_updates_existing_payment_without_duplicate(): void
    {
        $intentId = 'pi_test_client_first_' . uniqid();

        // 1. Client creates order and payment first
        $order = Order::create([
            'user_id'                => $this->user->id,
            'customer_email'         => $this->user->email,
            'address_id'             => $this->address->id,
            'shipping_name'          => $this->address->name,
            'shipping_phone'         => $this->address->phone,
            'shipping_address_line1' => $this->address->address_line1,
            'shipping_city'          => $this->address->city,
            'shipping_state'         => $this->address->state,
            'shipping_postal_code'   => $this->address->postal_code,
            'shipping_country'       => $this->address->country,
            'status'                 => 'pending',
            'total_amount'           => 100.00,
        ]);

        $payment = Payment::create([
            'order_id'                 => $order->id,
            'payment_method'           => 'card',
            'amount'                   => 100.00,
            'status'                   => 'pending',
            'stripe_payment_intent_id' => $intentId,
        ]);

        // 2. Webhook triggers handlePaymentIntentSucceeded
        $controller = app(StripeController::class);
        $mockIntent = (object) [
            'id'       => $intentId,
            'metadata' => (object) [
                'user_id'    => $this->user->id,
                'address_id' => $this->address->id,
            ],
        ];

        // Call private method via reflection
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('handlePaymentIntentSucceeded');
        $method->setAccessible(true);
        $method->invoke($controller, $mockIntent);

        // 3. Verify payment was updated to completed, order to processing, and NO duplicates exist
        $this->assertEquals('completed', $payment->fresh()->status);
        $this->assertEquals('processing', $order->fresh()->status);
        $this->assertEquals(1, Order::where('user_id', $this->user->id)->count());
        $this->assertEquals(1, Payment::where('stripe_payment_intent_id', $intentId)->count());
    }

    public function test_client_checkout_rejects_replayed_intent_when_cart_has_items(): void
    {
        $intentId = 'pi_test_replay_attack_' . uniqid();

        // Previous order already exists
        $prevOrder = Order::create([
            'user_id'      => $this->user->id,
            'address_id'   => $this->address->id,
            'status'       => 'completed',
            'total_amount' => 50.00,
        ]);

        Payment::create([
            'order_id'                 => $prevOrder->id,
            'payment_method'           => 'card',
            'amount'                   => 50.00,
            'status'                   => 'completed',
            'stripe_payment_intent_id' => $intentId,
        ]);

        // User has an active cart with an item
        $cart = Cart::create(['user_id' => $this->user->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $this->product->id,
            'quantity'   => 1,
        ]);

        // User attempts to reuse the previous payment intent
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/orders', [
                'address_id'               => $this->address->id,
                'payment_method'           => 'card',
                'stripe_payment_intent_id' => $intentId,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This payment has already been processed for an existing order.');
    }
}

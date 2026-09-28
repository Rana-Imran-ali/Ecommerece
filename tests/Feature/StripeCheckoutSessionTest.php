<?php

namespace Tests\Feature;

use App\Http\Controllers\StripeController;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeCheckoutSessionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private Address $address;
    private Product $product;
    private Cart $cart;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'David']);
        $this->token = $this->user->createToken('test-device')->plainTextToken;

        $category = Category::create(['name' => 'Laptops']);
        $this->product = Product::create([
            'category_id' => $category->id,
            'name'        => 'Gaming Laptop',
            'price'       => 1500.00,
            'stock'       => 10,
        ]);

        $this->address = Address::create([
            'user_id'       => $this->user->id,
            'name'          => 'David Home',
            'phone'         => '5551234567',
            'address_line1' => '789 Pine St',
            'city'          => 'Austin',
            'state'         => 'TX',
            'postal_code'   => '78701',
            'country'       => 'USA',
            'is_default'    => true,
        ]);

        $this->cart = Cart::create(['user_id' => $this->user->id]);
        CartItem::create([
            'cart_id'    => $this->cart->id,
            'product_id' => $this->product->id,
            'quantity'   => 2,
        ]);
    }

    public function test_create_session_does_not_deplete_stock_or_wipe_cart(): void
    {
        // Initial stock is 10
        $this->assertEquals(10, $this->product->fresh()->stock);
        // Cart has 1 item (quantity 2)
        $this->assertEquals(1, $this->cart->fresh()->items()->count());

        // Call create-session with mocked Stripe
        $stripeMock = \Mockery::mock(\Stripe\StripeClient::class);
        $checkoutMock = \Mockery::mock();
        $sessionsMock = \Mockery::mock();

        $sessionObj = (object) [
            'id'  => 'cs_test_session_123',
            'url' => 'https://checkout.stripe.com/c/pay/cs_test_session_123',
        ];

        $sessionsMock->shouldReceive('create')
            ->once()
            ->andReturn($sessionObj);

        $checkoutMock->sessions = $sessionsMock;
        $stripeMock->checkout = $checkoutMock;

        $this->app->instance(\Stripe\StripeClient::class, $stripeMock);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/stripe/create-session', [
                'address_id'     => $this->address->id,
                'customer_email' => 'david@example.com',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('session_id', 'cs_test_session_123');

        // CRUCIAL: Verify stock was NOT depleted (must remain 10)
        $this->assertEquals(10, $this->product->fresh()->stock);

        // CRUCIAL: Verify cart was NOT wiped
        $this->assertEquals(1, $this->cart->fresh()->items()->count());

        // CRUCIAL: Verify NO pending order or payment was prematurely created
        $this->assertEquals(0, Order::where('user_id', $this->user->id)->count());
        $this->assertEquals(0, Payment::count());
    }

    public function test_checkout_completed_webhook_fulfills_order_and_clears_cart(): void
    {
        $sessionId = 'cs_test_webhook_' . uniqid();
        $intentId  = 'pi_test_webhook_' . uniqid();

        $controller = app(StripeController::class);

        $mockSession = (object) [
            'id'             => $sessionId,
            'payment_intent' => $intentId,
            'amount_total'   => 300000, // $3000.00 (2 * $1500)
            'metadata'       => (object) [
                'user_id'        => (string) $this->user->id,
                'address_id'     => (string) $this->address->id,
                'customer_email' => $this->user->email,
                'coupon_code'    => '',
            ],
        ];

        // Trigger handleCheckoutCompleted via reflection
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('handleCheckoutCompleted');
        $method->setAccessible(true);
        $method->invoke($controller, $mockSession);

        // 1. Order should be created with status 'processing'
        $order = Order::where('user_id', $this->user->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals('processing', $order->status);
        $this->assertEquals('3000.00', (string) $order->total_amount);

        // 2. Stock decremented from 10 to 8
        $this->assertEquals(8, $this->product->fresh()->stock);

        // 3. Payment record created with status 'completed' and session ID
        $payment = Payment::where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('completed', $payment->status);
        $this->assertEquals($sessionId, $payment->stripe_session_id);
        $this->assertEquals($intentId, $payment->stripe_payment_intent_id);

        // 4. Cart should now be cleared
        $this->assertEquals(0, $this->cart->fresh()->items()->count());
    }

    public function test_cancel_page_preserves_cart_and_renders(): void
    {
        $response = $this->get('/payment/cancel');

        $response->assertOk()
            ->assertSee('Payment Cancelled')
            ->assertSee('Your items are safely saved in your shopping cart');

        // Cart items still exist
        $this->assertEquals(1, $this->cart->fresh()->items()->count());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentOrderSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;
    private Product $product;
    private Address $address;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->customer = User::factory()->create(['role' => 'customer']);

        $category = Category::create(['name' => 'Gadgets']);
        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Smart Watch',
            'price' => 150.00,
            'stock' => 10,
        ]);

        $this->address = Address::create([
            'user_id' => $this->customer->id,
            'name' => 'Customer Home',
            'phone' => '1234567890',
            'address_line1' => '100 Main St',
            'city' => 'Anytown',
            'state' => 'CA',
            'postal_code' => '90210',
            'country' => 'USA',
        ]);
    }

    private function createOrderWithPayment(string $paymentStatus = 'pending', string $orderStatus = 'pending', int $qty = 2): array
    {
        $order = Order::create([
            'user_id' => $this->customer->id,
            'customer_email' => $this->customer->email,
            'address_id' => $this->address->id,
            'status' => $orderStatus,
            'total_amount' => $this->product->price * $qty,
        ]);

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => $qty,
            'unit_price' => $this->product->price,
            'price' => $this->product->price,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_method' => 'bank_transfer',
            'amount' => $order->total_amount,
            'status' => $paymentStatus,
        ]);

        return [$order, $payment, $orderItem];
    }

    public function test_payment_status_completed_updates_order_to_processing(): void
    {
        [$order, $payment] = $this->createOrderWithPayment('pending', 'pending');

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.payments.status', $payment), [
                'status' => 'completed',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_payment_status_failed_updates_order_to_cancelled_and_restores_stock(): void
    {
        $initialStock = $this->product->stock; // 10
        [$order, $payment] = $this->createOrderWithPayment('pending', 'pending', 3);

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.payments.status', $payment), [
                'status' => 'failed',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'failed',
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);

        // Stock restored from 10 to 13
        $this->assertEquals($initialStock + 3, $this->product->fresh()->stock);

        // Inventory log created
        $this->assertDatabaseHas('inventory_logs', [
            'product_id' => $this->product->id,
            'type' => 'return',
            'quantity' => 3,
            'reference_id' => (string) $order->id,
        ]);
    }

    public function test_payment_status_refunded_updates_order_to_cancelled_and_restores_stock(): void
    {
        $initialStock = $this->product->stock; // 10
        [$order, $payment] = $this->createOrderWithPayment('completed', 'processing', 2);

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.payments.status', $payment), [
                'status' => 'refunded',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'refunded',
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);

        // Stock restored from 10 to 12
        $this->assertEquals($initialStock + 2, $this->product->fresh()->stock);

        // Inventory log created
        $this->assertDatabaseHas('inventory_logs', [
            'product_id' => $this->product->id,
            'type' => 'return',
            'quantity' => 2,
            'reference_id' => (string) $order->id,
        ]);
    }

    public function test_admin_order_cancellation_updates_pending_payment_to_cancelled(): void
    {
        [$order, $payment] = $this->createOrderWithPayment('pending', 'pending', 1);

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.orders.status', $order), [
                'status' => 'cancelled',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'cancelled',
        ]);
    }
}

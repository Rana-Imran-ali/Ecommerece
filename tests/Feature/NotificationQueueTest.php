<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusNotification;
use App\Observers\OrderObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationQueueTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Address $address;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'customer@example.com',
        ]);

        $this->address = Address::create([
            'user_id'       => $this->user->id,
            'name'          => 'John Doe',
            'phone'         => '1234567890',
            'address_line1' => '123 Test St',
            'city'          => 'Testville',
            'state'         => 'TS',
            'postal_code'   => '12345',
            'country'       => 'Testland',
        ]);
    }

    public function test_order_status_notification_implements_queue_settings(): void
    {
        $order = Order::create([
            'user_id'      => $this->user->id,
            'address_id'   => $this->address->id,
            'total_amount' => 100.00,
            'status'       => 'pending',
        ]);

        $notification = new OrderStatusNotification($order, 'status');

        $this->assertEquals('notifications', $notification->queue);
        $this->assertEquals(3, $notification->tries);
        $this->assertEquals(30, $notification->timeout);
        $this->assertEquals([60, 120], $notification->backoff());
        $this->assertInstanceOf(\DateTime::class, $notification->retryUntil());
    }

    public function test_order_creation_dispatches_notification_to_recipient_email(): void
    {
        Notification::fake();

        $order = Order::create([
            'user_id'      => $this->user->id,
            'address_id'   => $this->address->id,
            'total_amount' => 150.00,
            'status'       => 'pending',
        ]);

        $observer = new OrderObserver();
        $observer->dispatchNotification($order, 'status', 'test_run');

        Notification::assertSentOnDemand(
            OrderStatusNotification::class,
            function (OrderStatusNotification $notification, $channels, $notifiable) use ($order) {
                return $notifiable->routes['mail'] === 'customer@example.com'
                    && $notification->order->id === $order->id
                    && $notification->queue === 'notifications';
            }
        );
    }

    public function test_notification_skipped_if_recipient_email_missing(): void
    {
        Notification::fake();

        $order = Order::create([
            'user_id'        => null,
            'customer_email' => null,
            'address_id'     => $this->address->id,
            'total_amount'   => 50.00,
            'status'         => 'pending',
        ]);

        $observer = new OrderObserver();
        $observer->dispatchNotification($order, 'status', 'test_empty_email');

        Notification::assertNothingSent();
    }
}

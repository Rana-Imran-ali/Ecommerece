<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserOrderRelationshipTest extends TestCase
{
    use RefreshDatabase;
    public function test_user_has_many_orders_and_order_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $order = $user->orders()->create([]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $order->user);
        $this->assertEquals($user->id, $order->user->id);
        $this->assertTrue($user->orders->contains($order));

        // Cleanup
        $order->delete();
        $user->delete();
    }
}

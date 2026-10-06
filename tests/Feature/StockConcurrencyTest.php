<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private User $user1;
    private string $token1;
    private User $user2;
    private string $token2;
    private User $admin;
    private string $adminToken;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Electronics']);

        $this->user1 = User::factory()->create(['role' => 'customer']);
        $this->token1 = $this->user1->createToken('token1')->plainTextToken;

        $this->user2 = User::factory()->create(['role' => 'customer']);
        $this->token2 = $this->user2->createToken('token2')->plainTextToken;

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->adminToken = $this->admin->createToken('admin-token')->plainTextToken;
    }

    /** @test */
    public function simultaneous_checkout_on_last_item_prevents_negative_inventory(): void
    {
        // Product with exactly 1 unit in stock
        $product = Product::create([
            'category_id' => $this->category->id,
            'name'        => 'Limited Edition Headphones',
            'description' => 'Rare headphones',
            'price'       => 100.00,
            'stock'       => 1,
            'status'      => 'active',
        ]);

        $addr1 = Address::create([
            'user_id'       => $this->user1->id,
            'name'          => 'User One',
            'phone'         => '1111111111',
            'address_line1' => '123 Main St',
            'city'          => 'New York',
            'state'         => 'NY',
            'postal_code'   => '10001',
            'country'       => 'US',
        ]);

        $addr2 = Address::create([
            'user_id'       => $this->user2->id,
            'name'          => 'User Two',
            'phone'         => '2222222222',
            'address_line1' => '456 Elm St',
            'city'          => 'Boston',
            'state'         => 'MA',
            'postal_code'   => '02108',
            'country'       => 'US',
        ]);

        // User 1 adds 1 to cart
        $cart1 = Cart::create(['user_id' => $this->user1->id]);
        CartItem::create([
            'cart_id'    => $cart1->id,
            'product_id' => $product->id,
            'quantity'   => 1,
        ]);

        // User 2 adds 1 to cart
        $cart2 = Cart::create(['user_id' => $this->user2->id]);
        CartItem::create([
            'cart_id'    => $cart2->id,
            'product_id' => $product->id,
            'quantity'   => 1,
        ]);

        // User 1 checks out first
        $res1 = $this->withHeaders(['Authorization' => "Bearer {$this->token1}"])
            ->postJson('/api/orders', [
                'payment_method' => 'cod',
                'address_id'     => $addr1->id,
            ]);

        $res1->assertStatus(201)->assertJsonPath('success', true);

        // Product stock is now 0
        $this->assertEquals(0, $product->fresh()->stock);

        // User 2 attempts to checkout the same item
        $res2 = $this->withHeaders(['Authorization' => "Bearer {$this->token2}"])
            ->postJson('/api/orders', [
                'payment_method' => 'cod',
                'address_id'     => $addr2->id,
            ]);

        $res2->assertStatus(422)
            ->assertJsonPath('success', false);

        // Stock must remain 0 and never drop negative
        $this->assertEquals(0, $product->fresh()->stock);
        $this->assertDatabaseCount('orders', 1);
    }

    /** @test */
    public function simultaneous_variant_checkout_prevents_negative_variant_stock(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name'        => 'Gaming Mouse',
            'description' => 'RGB Mouse',
            'price'       => 50.00,
            'stock'       => 10, // Parent stock
            'status'      => 'active',
        ]);

        $option = ProductOption::create(['product_id' => $product->id, 'name' => 'Color']);
        $optVal = ProductOptionValue::create(['product_option_id' => $option->id, 'value' => 'White']);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku'        => 'MOUSE-WHT',
            'price'      => 55.00,
            'stock'      => 1, // Only 1 white mouse in stock
            'status'     => 'active',
        ]);
        $variant->optionValues()->sync([$optVal->id]);

        $addr1 = Address::create([
            'user_id'       => $this->user1->id,
            'name'          => 'User One',
            'phone'         => '1111111111',
            'address_line1' => '123 Main St',
            'city'          => 'New York',
            'state'         => 'NY',
            'postal_code'   => '10001',
            'country'       => 'US',
        ]);

        $addr2 = Address::create([
            'user_id'       => $this->user2->id,
            'name'          => 'User Two',
            'phone'         => '2222222222',
            'address_line1' => '456 Elm St',
            'city'          => 'Boston',
            'state'         => 'MA',
            'postal_code'   => '02108',
            'country'       => 'US',
        ]);

        $cart1 = Cart::create(['user_id' => $this->user1->id]);
        CartItem::create([
            'cart_id'            => $cart1->id,
            'product_id'         => $product->id,
            'product_variant_id' => $variant->id,
            'quantity'           => 1,
        ]);

        $cart2 = Cart::create(['user_id' => $this->user2->id]);
        CartItem::create([
            'cart_id'            => $cart2->id,
            'product_id'         => $product->id,
            'product_variant_id' => $variant->id,
            'quantity'           => 1,
        ]);

        // User 1 buys the only variant
        $res1 = $this->withHeaders(['Authorization' => "Bearer {$this->token1}"])
            ->postJson('/api/orders', [
                'payment_method' => 'cod',
                'address_id'     => $addr1->id,
            ]);

        $res1->assertStatus(201);
        $this->assertEquals(0, $variant->fresh()->stock);
        // Parent product stock synchronizes with variant stock
        $this->assertEquals(0, $product->fresh()->stock);

        // User 2 attempts to checkout the same variant
        $res2 = $this->withHeaders(['Authorization' => "Bearer {$this->token2}"])
            ->postJson('/api/orders', [
                'payment_method' => 'cod',
                'address_id'     => $addr2->id,
            ]);

        $res2->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertEquals(0, $variant->fresh()->stock);
        $this->assertEquals(0, $product->fresh()->stock);
    }

    /** @test */
    public function admin_cancelling_variant_order_only_restores_variant_stock(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name'        => 'Sweater',
            'description' => 'Wool sweater',
            'price'       => 60.00,
            'stock'       => 50,
            'status'      => 'active',
        ]);

        $option = ProductOption::create(['product_id' => $product->id, 'name' => 'Size']);
        $optVal = ProductOptionValue::create(['product_option_id' => $option->id, 'value' => 'L']);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku'        => 'SWEATER-L',
            'price'      => 60.00,
            'stock'      => 5,
            'status'     => 'active',
        ]);
        $variant->optionValues()->sync([$optVal->id]);

        $addr = Address::create([
            'user_id'       => $this->user1->id,
            'name'          => 'User One',
            'phone'         => '1111111111',
            'address_line1' => '123 Main St',
            'city'          => 'New York',
            'state'         => 'NY',
            'postal_code'   => '10001',
            'country'       => 'US',
        ]);

        $cart = Cart::create(['user_id' => $this->user1->id]);
        CartItem::create([
            'cart_id'            => $cart->id,
            'product_id'         => $product->id,
            'product_variant_id' => $variant->id,
            'quantity'           => 2,
        ]);

        // Place order: variant stock drops 5 -> 3, product stock synchronizes 5 -> 3
        $this->withHeaders(['Authorization' => "Bearer {$this->token1}"])
            ->postJson('/api/orders', [
                'payment_method' => 'cod',
                'address_id'     => $addr->id,
            ]);

        $this->assertEquals(3, $variant->fresh()->stock);
        $this->assertEquals(3, $product->fresh()->stock);

        $order = Order::latest('id')->first();

        // Admin cancels the order
        $this->actingAs($this->admin)
            ->patch("/admin/orders/{$order->id}/status", [
                'status' => 'cancelled',
            ]);

        // Variant stock restored 3 -> 5
        $this->assertEquals(5, $variant->fresh()->stock);
        // Parent product stock synchronizes 3 -> 5
        $this->assertEquals(5, $product->fresh()->stock);
    }
}

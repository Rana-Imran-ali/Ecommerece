<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariantStockTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $adminToken;
    private User $customer;
    private string $customerToken;
    private Category $category;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Clothing']);

        $this->product = Product::create([
            'name'        => 'T-Shirt',
            'description' => 'A cotton T-shirt',
            'price'       => 25.00,
            'stock'       => 100, // Parent stock — must NOT change when a variant order is placed
            'category_id' => $this->category->id,
            'status'      => 'active',
        ]);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->adminToken = $this->admin->createToken('test-admin')->plainTextToken;

        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->customerToken = $this->customer->createToken('test-customer')->plainTextToken;
    }

    // =========================================================================
    // Bug Fix: Dual Stock Deduction
    // =========================================================================

    /** @test */
    public function placing_variant_order_deducts_variant_stock_and_synchronizes_parent_stock(): void
    {
        // Set up a Size option + "M" variant with 10 units
        $option = ProductOption::create(['product_id' => $this->product->id, 'name' => 'Size']);
        $optVal = ProductOptionValue::create(['product_option_id' => $option->id, 'value' => 'M']);
        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku'        => 'TSHIRT-M',
            'price'      => 25.00,
            'stock'      => 10,
            'status'     => 'active',
        ]);
        $variant->optionValues()->sync([$optVal->id]);

        // Parent stock was automatically synchronized to variant stock (10)
        $this->assertEquals(10, $this->product->fresh()->stock);

        // Customer adds 3 × "M" variant to cart
        $address = \App\Models\Address::create([
            'user_id'       => $this->customer->id,
            'name'          => 'Test User',
            'phone'         => '1234567890',
            'address_line1' => '123 Street',
            'city'          => 'City',
            'state'         => 'State',
            'postal_code'   => '00000',
            'country'       => 'US',
        ]);

        $cart = Cart::create(['user_id' => $this->customer->id]);
        CartItem::create([
            'cart_id'            => $cart->id,
            'product_id'         => $this->product->id,
            'product_variant_id' => $variant->id,
            'quantity'           => 3,
        ]);

        // Place a COD order
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->customerToken}"])
            ->postJson('/api/orders', [
                'payment_method' => 'cod',
                'address_id'     => $address->id,
            ]);

        $response->assertStatus(201)->assertJsonPath('success', true);

        // Variant stock should drop from 10 → 7
        $this->assertDatabaseHas('product_variants', [
            'id'    => $variant->id,
            'stock' => 7,
        ]);

        // Parent product stock must synchronize to match actual available variant stock (7)
        $this->assertDatabaseHas('products', [
            'id'    => $this->product->id,
            'stock' => 7,
        ]);
    }

    /** @test */
    public function cancelling_variant_order_restores_variant_stock_and_synchronizes_parent_stock(): void
    {
        $option = ProductOption::create(['product_id' => $this->product->id, 'name' => 'Size']);
        $optVal = ProductOptionValue::create(['product_option_id' => $option->id, 'value' => 'L']);
        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku'        => 'TSHIRT-L',
            'price'      => 25.00,
            'stock'      => 5,
            'status'     => 'active',
        ]);
        $variant->optionValues()->sync([$optVal->id]);

        $address = \App\Models\Address::create([
            'user_id'       => $this->customer->id,
            'name'          => 'Test User',
            'phone'         => '1234567890',
            'address_line1' => '123 Street',
            'city'          => 'City',
            'state'         => 'State',
            'postal_code'   => '00000',
            'country'       => 'US',
        ]);

        $cart = Cart::create(['user_id' => $this->customer->id]);
        CartItem::create([
            'cart_id'            => $cart->id,
            'product_id'         => $this->product->id,
            'product_variant_id' => $variant->id,
            'quantity'           => 2,
        ]);

        // Place order (variant stock 5 → 3, parent synchronizes 5 → 3)
        $this->withHeaders(['Authorization' => "Bearer {$this->customerToken}"])
            ->postJson('/api/orders', ['payment_method' => 'cod', 'address_id' => $address->id])
            ->assertStatus(201);

        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 3]);
        $this->assertDatabaseHas('products', ['id' => $this->product->id, 'stock' => 3]);

        $order = Order::where('user_id', $this->customer->id)->latest()->first();

        // Cancel order — should restore variant stock 3 → 5, parent synchronizes 3 → 5
        $this->withHeaders(['Authorization' => "Bearer {$this->customerToken}"])
            ->patchJson("/api/orders/{$order->id}/cancel")
            ->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 5]);
        $this->assertDatabaseHas('products', ['id' => $this->product->id, 'stock' => 5]);
    }

    /** @test */
    public function catalog_displays_actual_available_variant_stock(): void
    {
        $option = ProductOption::create(['product_id' => $this->product->id, 'name' => 'Size']);
        $optS = ProductOptionValue::create(['product_option_id' => $option->id, 'value' => 'S']);
        $optM = ProductOptionValue::create(['product_option_id' => $option->id, 'value' => 'M']);

        $varS = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku'        => 'TSHIRT-S-CAT',
            'price'      => 25.00,
            'stock'      => 4,
            'status'     => 'active',
        ]);
        $varS->optionValues()->sync([$optS->id]);

        $varM = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku'        => 'TSHIRT-M-CAT',
            'price'      => 25.00,
            'stock'      => 6,
            'status'     => 'active',
        ]);
        $varM->optionValues()->sync([$optM->id]);

        // Total available variant stock is 4 + 6 = 10
        $response = $this->getJson("/api/products/{$this->product->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.stock', 10)
            ->assertJsonPath('data.in_stock', true);

        // Also listing in catalog shows actual available variant stock
        $listResponse = $this->getJson('/api/products?search=' . urlencode($this->product->name));
        $listResponse->assertStatus(200);
        $item = collect($listResponse->json('data'))->firstWhere('id', $this->product->id);
        $this->assertNotNull($item);
        $this->assertEquals(10, $item['stock']);
    }

    /** @test */
    public function non_variant_order_deducts_parent_stock_as_before(): void
    {
        $address = \App\Models\Address::create([
            'user_id'       => $this->customer->id,
            'name'          => 'Test User',
            'phone'         => '1234567890',
            'address_line1' => '123 Street',
            'city'          => 'City',
            'state'         => 'State',
            'postal_code'   => '00000',
            'country'       => 'US',
        ]);

        $cart = Cart::create(['user_id' => $this->customer->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $this->product->id,
            'quantity'   => 5,
        ]);

        $this->withHeaders(['Authorization' => "Bearer {$this->customerToken}"])
            ->postJson('/api/orders', ['payment_method' => 'cod', 'address_id' => $address->id])
            ->assertStatus(201);

        // Parent stock 100 → 95; no variants involved
        $this->assertDatabaseHas('products', ['id' => $this->product->id, 'stock' => 95]);
    }

    // =========================================================================
    // Admin Variant Management Endpoints
    // =========================================================================

    /** @test */
    public function admin_can_create_product_option_with_values(): void
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])
            ->postJson("/api/products/{$this->product->id}/options", [
                'name'   => 'Color',
                'values' => ['Red', 'Blue', 'Green'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Color');

        $this->assertDatabaseHas('product_options', ['product_id' => $this->product->id, 'name' => 'Color']);
        $this->assertDatabaseHas('product_option_values', ['value' => 'Red']);
        $this->assertDatabaseHas('product_option_values', ['value' => 'Blue']);
        $this->assertDatabaseHas('product_option_values', ['value' => 'Green']);
    }

    /** @test */
    public function admin_can_create_variant_linked_to_option_values(): void
    {
        $option = ProductOption::create(['product_id' => $this->product->id, 'name' => 'Size']);
        $optVal = ProductOptionValue::create(['product_option_id' => $option->id, 'value' => 'XL']);

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])
            ->postJson("/api/products/{$this->product->id}/variants", [
                'sku'              => 'TSHIRT-XL',
                'price'            => 30.00,
                'stock'            => 20,
                'status'           => 'active',
                'option_value_ids' => [$optVal->id],
            ]);
        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.sku', 'TSHIRT-XL')
            ->assertJsonPath('data.stock', 20);

        // effective_price may be encoded as int 30 or float 30.0 — check numerically
        $this->assertEquals(30.0, $response->json('data.effective_price'));

        $this->assertDatabaseHas('product_variants', ['sku' => 'TSHIRT-XL', 'stock' => 20]);

        // Inventory log must record the initial stock
        $this->assertDatabaseHas('inventory_logs', [
            'product_id'      => $this->product->id,
            'type'            => 'initial',
            'quantity_before' => 0,
            'quantity_after'  => 20,
        ]);
    }

    /** @test */
    public function admin_can_update_variant_stock_and_logs_adjustment(): void
    {
        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku'        => 'TSHIRT-S',
            'price'      => 25.00,
            'stock'      => 15,
            'status'     => 'active',
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])
            ->putJson("/api/products/{$this->product->id}/variants/{$variant->id}", [
                'stock' => 30,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.stock', 30);

        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 30]);

        // Stock changed 15 → 30, diff = +15, must be logged
        $this->assertDatabaseHas('inventory_logs', [
            'product_variant_id' => $variant->id,
            'type'               => 'adjustment',
            'quantity'           => 15,
            'quantity_before'    => 15,
            'quantity_after'     => 30,
        ]);
    }

    /** @test */
    public function admin_can_soft_delete_variant(): void
    {
        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku'        => 'TSHIRT-DELETE',
            'price'      => 25.00,
            'stock'      => 8,
            'status'     => 'active',
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])
            ->deleteJson("/api/products/{$this->product->id}/variants/{$variant->id}");

        $response->assertStatus(200)->assertJsonPath('success', true);

        // Soft-deleted — not in main query
        $this->assertSoftDeleted('product_variants', ['id' => $variant->id]);

        // Audit log zeroes out the stock
        $this->assertDatabaseHas('inventory_logs', [
            'product_variant_id' => $variant->id,
            'type'               => 'adjustment',
            'quantity'           => -8,
            'quantity_before'    => 8,
            'quantity_after'     => 0,
        ]);
    }

    /** @test */
    public function customer_cannot_manage_variants(): void
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->customerToken}"])
            ->postJson("/api/products/{$this->product->id}/variants", ['stock' => 5])
            ->assertStatus(403);

        $this->withHeaders(['Authorization' => "Bearer {$this->customerToken}"])
            ->postJson("/api/products/{$this->product->id}/options", ['name' => 'Size', 'values' => ['S']])
            ->assertStatus(403);
    }

    /** @test */
    public function variant_update_belonging_to_different_product_is_rejected(): void
    {
        $otherProduct = Product::create([
            'name'        => 'Jacket',
            'description' => 'A jacket',
            'price'       => 80.00,
            'stock'       => 20,
            'category_id' => $this->category->id,
            'status'      => 'active',
        ]);

        $variantOfOther = ProductVariant::create([
            'product_id' => $otherProduct->id,
            'sku'        => 'JACKET-M',
            'stock'      => 10,
            'status'     => 'active',
        ]);

        // Try to update variantOfOther via the T-Shirt route — should 404
        $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])
            ->putJson("/api/products/{$this->product->id}/variants/{$variantOfOther->id}", ['stock' => 5])
            ->assertStatus(404)
            ->assertJsonPath('message', 'Variant does not belong to this product.');
    }
}

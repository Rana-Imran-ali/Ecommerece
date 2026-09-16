<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Clothing',
            'slug' => 'clothing',
        ]);

        $this->product = Product::create([
            'category_id' => $this->category->id,
            'name'        => 'Classic T-Shirt',
            'description' => 'A comfortable cotton t-shirt.',
            'price'       => 29.99,
            'stock'       => 100,
            'status'      => 'active',
        ]);
    }

    public function test_can_create_product_options_and_values(): void
    {
        $sizeOption = ProductOption::create([
            'product_id' => $this->product->id,
            'name'       => 'Size',
        ]);

        $smallValue = ProductOptionValue::create([
            'product_option_id' => $sizeOption->id,
            'value'             => 'Small',
        ]);

        $mediumValue = ProductOptionValue::create([
            'product_option_id' => $sizeOption->id,
            'value'             => 'Medium',
        ]);

        $this->assertCount(1, $this->product->options);
        $this->assertEquals('Size', $this->product->options->first()->name);
        $this->assertCount(2, $sizeOption->values);
        $this->assertEquals('Small', $smallValue->option->name === 'Size' ? $smallValue->value : null);
    }

    public function test_can_create_variant_and_link_to_option_values(): void
    {
        $sizeOption = ProductOption::create([
            'product_id' => $this->product->id,
            'name'       => 'Size',
        ]);
        $colorOption = ProductOption::create([
            'product_id' => $this->product->id,
            'name'       => 'Color',
        ]);

        $small = ProductOptionValue::create([
            'product_option_id' => $sizeOption->id,
            'value'             => 'Small',
        ]);
        $red = ProductOptionValue::create([
            'product_option_id' => $colorOption->id,
            'value'             => 'Red',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku'        => 'TSHIRT-RED-S',
            'price'      => 34.99,
            'stock'      => 15,
            'status'     => 'active',
        ]);

        $variant->optionValues()->attach([$small->id, $red->id]);

        $this->assertCount(1, $this->product->variants);
        $this->assertEquals('TSHIRT-RED-S', $this->product->variants->first()->sku);
        $this->assertEquals(34.99, $variant->effective_price);

        // Option values linked to variant
        $this->assertCount(2, $variant->optionValues);
        $this->assertTrue($small->variants->contains($variant));
        $this->assertTrue($red->variants->contains($variant));
    }

    public function test_variant_effective_price_falls_back_to_product_price_when_null(): void
    {
        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku'        => 'TSHIRT-DEFAULT',
            'price'      => null,
            'stock'      => 10,
            'status'     => 'active',
        ]);

        $this->assertEquals(29.99, $variant->effective_price);
    }

    public function test_cart_item_can_reference_product_variant(): void
    {
        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);

        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku'        => 'TSHIRT-VAR-1',
            'price'      => 32.00,
            'stock'      => 5,
            'status'     => 'active',
        ]);

        $cartItem = CartItem::create([
            'cart_id'            => $cart->id,
            'product_id'         => $this->product->id,
            'product_variant_id' => $variant->id,
            'quantity'           => 2,
        ]);

        $this->assertEquals($variant->id, $cartItem->variant->id);
        $this->assertEquals('TSHIRT-VAR-1', $cartItem->variant->sku);
        $this->assertCount(1, $variant->cartItems);
    }

    public function test_order_item_can_reference_product_variant_and_store_variant_name(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'user_id'          => $user->id,
            'status'           => 'pending',
            'subtotal'         => 64.00,
            'shipping_fee'     => 0.00,
            'tax'              => 0.00,
            'discount'         => 0.00,
            'total_amount'     => 64.00,
            'shipping_address' => '123 Test St',
            'billing_address'  => '123 Test St',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku'        => 'TSHIRT-RED-M',
            'price'      => 32.00,
            'stock'      => 10,
            'status'     => 'active',
        ]);

        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'product_id'         => $this->product->id,
            'product_variant_id' => $variant->id,
            'product_name'       => $this->product->name,
            'variant_name'       => 'Color: Red / Size: Medium',
            'quantity'           => 2,
            'price'              => 32.00,
        ]);

        $this->assertEquals($variant->id, $orderItem->variant->id);
        $this->assertEquals('Color: Red / Size: Medium', $orderItem->variant_name);
        $this->assertCount(1, $variant->orderItems);
    }

    public function test_inventory_log_tracks_product_variant_id(): void
    {
        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku'        => 'TSHIRT-LOG-TEST',
            'price'      => 29.99,
            'stock'      => 20,
            'status'     => 'active',
        ]);

        $log = InventoryLog::create([
            'product_id'         => $this->product->id,
            'product_variant_id' => $variant->id,
            'type'               => 'stock_in',
            'quantity'           => 20,
            'quantity_before'    => 0,
            'quantity_after'     => 20,
            'notes'              => 'Variant initial stock',
        ]);

        $this->assertEquals($variant->id, $log->variant->id);
        $this->assertCount(1, $variant->inventoryLogs);
    }

    public function test_deleting_product_cascades_to_options_and_variants(): void
    {
        $option = ProductOption::create([
            'product_id' => $this->product->id,
            'name'       => 'Material',
        ]);
        $value = ProductOptionValue::create([
            'product_option_id' => $option->id,
            'value'             => 'Cotton',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku'        => 'TSHIRT-COTTON',
            'price'      => 30.00,
            'stock'      => 10,
            'status'     => 'active',
        ]);
        $variant->optionValues()->attach($value->id);

        // Force delete to test database cascade
        $this->product->forceDelete();

        $this->assertDatabaseMissing('product_options', ['id' => $option->id]);
        $this->assertDatabaseMissing('product_option_values', ['id' => $value->id]);
        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
        $this->assertDatabaseMissing('product_variant_values', ['product_variant_id' => $variant->id]);
    }
}

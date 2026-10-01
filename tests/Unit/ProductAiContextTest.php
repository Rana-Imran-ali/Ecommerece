<?php

namespace Tests\Unit;

use App\Services\ProductAiContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAiContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_identifies_count_and_menu_inquiry()
    {
        $service = new ProductAiContextService();

        $context1 = $service->resolveContext('how many product do you have?');
        $this->assertEquals('catalog_count_and_menu', $context1['query_intent']);
        $this->assertArrayHasKey('total_active_products', $context1);
        $this->assertArrayHasKey('categories_summary', $context1);
        $this->assertArrayHasKey('store_menu', $context1);

        $context2 = $service->resolveContext('show me the product menu');
        $this->assertEquals('catalog_count_and_menu', $context2['query_intent']);
    }

    public function test_it_identifies_specific_product_and_price_inquiry()
    {
        $service = new ProductAiContextService();

        $context = $service->resolveContext('what is the price of wireless headphone?');
        $this->assertEquals('specific_product_inquiry', $context['query_intent']);
        $this->assertArrayHasKey('extracted_keywords', $context);
        $this->assertArrayHasKey('matched_products', $context);
        $this->assertArrayHasKey('items_found_count', $context);
    }

    public function test_it_filters_correct_product_data_and_price_from_database()
    {
        $category = \App\Models\Category::create(['name' => 'Footwear']);

        $product = \App\Models\Product::create([
            'category_id' => $category->id,
            'name'        => 'Nike Air Running Shoes',
            'description' => 'Comfortable road running footwear',
            'price'       => 129.99,
            'stock'       => 8,
            'status'      => 'active',
        ]);

        $service = new ProductAiContextService();

        // Specific price question
        $context = $service->resolveContext('what is the price of Nike Air?');
        $this->assertEquals('specific_product_inquiry', $context['query_intent']);
        $this->assertEquals(1, $context['items_found_count']);
        $this->assertEquals('Nike Air Running Shoes', $context['matched_products'][0]['name']);
        $this->assertEquals(129.99, $context['matched_products'][0]['price']);
        $this->assertStringContainsString('In Stock (8 units)', $context['matched_products'][0]['stock_status']);

        // Count question
        $countContext = $service->resolveContext('how many product do you have?');
        $this->assertEquals('catalog_count_and_menu', $countContext['query_intent']);
        $this->assertEquals(1, $countContext['total_active_products']);
        $this->assertEquals('Footwear', $countContext['categories_summary'][0]['category_name']);
        $this->assertEquals(1, $countContext['categories_summary'][0]['active_products_count']);
    }

    public function test_it_handles_product_not_found_cleanly()
    {
        $service = new ProductAiContextService();
        $context = $service->resolveContext('how much is Rolex Submariner?');

        $this->assertEquals('specific_product_inquiry', $context['query_intent']);
        $this->assertEquals(0, $context['items_found_count']);
        $this->assertEmpty($context['matched_products']);
    }

    public function test_it_identifies_non_product_queries_and_returns_general_context()
    {
        $service = new ProductAiContextService();

        $returnPolicy = $service->resolveContext('what is your return policy?');
        $this->assertEquals('general_store_inquiry', $returnPolicy['query_intent']);

        $shipping = $service->resolveContext('how long does shipping and delivery take?');
        $this->assertEquals('general_store_inquiry', $shipping['query_intent']);

        $contact = $service->resolveContext('how do I contact support or phone agent?');
        $this->assertEquals('general_store_inquiry', $contact['query_intent']);

        $payment = $service->resolveContext('what payment methods do you accept, can I pay with credit card?');
        $this->assertEquals('general_store_inquiry', $payment['query_intent']);
    }

    public function test_it_excludes_inactive_or_soft_deleted_products()
    {
        $category = \App\Models\Category::create(['name' => 'Gadgets']);

        $inactiveProduct = \App\Models\Product::create([
            'category_id' => $category->id,
            'name'        => 'Secret Prototype Widget',
            'description' => 'Unreleased item',
            'price'       => 999.00,
            'stock'       => 10,
            'status'      => 'inactive',
        ]);

        $deletedProduct = \App\Models\Product::create([
            'category_id' => $category->id,
            'name'        => 'Discontinued Ancient Gadget',
            'description' => 'No longer sold',
            'price'       => 50.00,
            'stock'       => 10,
            'status'      => 'active',
        ]);
        $deletedProduct->delete(); // Soft delete

        $service = new ProductAiContextService();

        $contextInactive = $service->resolveContext('how much is Secret Prototype Widget?');
        $this->assertEquals(0, $contextInactive['items_found_count']);

        $contextDeleted = $service->resolveContext('how much is Discontinued Ancient Gadget?');
        $this->assertEquals(0, $contextDeleted['items_found_count']);
    }

    public function test_it_includes_product_variants_when_available()
    {
        $category = \App\Models\Category::create(['name' => 'Apparel']);

        $product = \App\Models\Product::create([
            'category_id' => $category->id,
            'name'        => 'Premium Cotton T-Shirt',
            'description' => '100% organic cotton tee',
            'price'       => 29.99,
            'stock'       => 20,
            'status'      => 'active',
        ]);

        \App\Models\ProductVariant::create([
            'product_id' => $product->id,
            'sku'        => 'TEE-BLK-M',
            'price'      => 34.99,
            'stock'      => 7,
        ]);

        $service = new ProductAiContextService();
        $context = $service->resolveContext('tell me about Premium Cotton T-Shirt');

        $this->assertEquals('specific_product_inquiry', $context['query_intent']);
        $this->assertEquals(1, $context['items_found_count']);
        $this->assertNotEmpty($context['matched_products'][0]['variants']);
        $this->assertEquals('TEE-BLK-M', $context['matched_products'][0]['variants'][0]['sku']);
        $this->assertEquals(34.99, $context['matched_products'][0]['variants'][0]['price']);
        $this->assertEquals(7, $context['matched_products'][0]['variants'][0]['stock']);
    }
}

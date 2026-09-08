<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::create(['name' => 'Electronics']);
    }

    public function test_can_list_products_with_filters_and_search(): void
    {
        Product::create([
            'category_id' => $this->category->id,
            'name' => 'Wireless Mouse',
            'description' => 'Ergonomic 2.4G wireless optical mouse',
            'price' => 29.99,
            'stock' => 10,
        ]);

        Product::create([
            'category_id' => $this->category->id,
            'name' => 'Mechanical Keyboard',
            'description' => 'RGB backlit mechanical keyboard',
            'price' => 89.99,
            'stock' => 0,
        ]);

        // Test search filter
        $response = $this->getJson('/api/products?search=Mouse');
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Wireless Mouse');

        // Test in_stock filter
        $inStockResponse = $this->getJson('/api/products?in_stock=true');
        $inStockResponse->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Wireless Mouse');

        // Test price range filter
        $priceResponse = $this->getJson('/api/products?min_price=50');
        $priceResponse->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Mechanical Keyboard');
    }

    public function test_can_create_product_with_validation_and_images(): void
    {
        Storage::fake('public');

        $payload = [
            'category_id' => $this->category->id,
            'name' => 'Gaming Headset',
            'description' => 'Surround sound stereo headset',
            'price' => 59.99,
            'stock' => 25,
            'images' => [
                UploadedFile::fake()->create('headset1.jpg', 100, 'image/jpeg'),
                UploadedFile::fake()->create('headset2.jpg', 100, 'image/jpeg'),
            ],
            'primary_image_index' => 0,
        ];

        $response = $this->postJson('/api/products', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Gaming Headset');

        $this->assertDatabaseHas('products', [
            'name' => 'Gaming Headset',
            'stock' => 25,
        ]);

        $this->assertDatabaseCount('product_images', 2);
        $this->assertDatabaseHas('product_images', ['is_primary' => true]);
    }

    public function test_create_product_fails_with_invalid_data(): void
    {
        $response = $this->postJson('/api/products', [
            'name' => '',
            'price' => -10,
            'stock' => -5,
            'category_id' => 99999, // non-existent
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'price', 'stock', 'category_id']);
    }

    public function test_can_show_product_details(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'USB-C Cable',
            'description' => 'Fast charging cable',
            'price' => 9.99,
            'stock' => 50,
        ]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'USB-C Cable')
            ->assertJsonPath('data.in_stock', true);
    }

    public function test_can_update_product(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Original Monitor',
            'description' => '1080p Monitor',
            'price' => 150.00,
            'stock' => 5,
        ]);

        $response = $this->putJson("/api/products/{$product->id}", [
            'name' => 'Updated 4K Monitor',
            'price' => 299.99,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated 4K Monitor');

        $this->assertEquals(299.99, (float) $response->json('data.price'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated 4K Monitor',
        ]);
    }

    public function test_can_delete_product_and_clean_up_images(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Tablet',
            'price' => 200.00,
            'stock' => 2,
        ]);

        $imagePath = 'products/fake_tablet.jpg';
        Storage::disk('public')->put($imagePath, 'fake-content');

        ProductImage::create([
            'product_id' => $product->id,
            'image' => $imagePath,
            'is_primary' => true,
        ]);

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_images', ['image' => $imagePath]);
        Storage::disk('public')->assertMissing($imagePath);
    }

    public function test_can_upload_additional_images_and_set_primary(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Smart Watch',
            'price' => 199.99,
            'stock' => 15,
        ]);

        // Upload first image
        $uploadResponse = $this->postJson("/api/products/{$product->id}/images", [
            'image' => UploadedFile::fake()->create('watch1.jpg', 100, 'image/jpeg'),
        ]);

        $uploadResponse->assertCreated();
        $firstImageId = $uploadResponse->json('data.0.id');

        // Upload second image and designate as primary
        $secondUpload = $this->postJson("/api/products/{$product->id}/images", [
            'image' => UploadedFile::fake()->create('watch2.jpg', 100, 'image/jpeg'),
            'is_primary' => true,
        ]);

        $secondUpload->assertCreated();
        $secondImageId = $secondUpload->json('data.0.id');

        $this->assertDatabaseHas('product_images', [
            'id' => $firstImageId,
            'is_primary' => false,
        ]);
        $this->assertDatabaseHas('product_images', [
            'id' => $secondImageId,
            'is_primary' => true,
        ]);
    }

    public function test_stock_validation(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Laptop Stand',
            'price' => 35.00,
            'stock' => 5,
        ]);

        // Available quantity
        $responseOk = $this->postJson("/api/products/{$product->id}/check-stock", [
            'quantity' => 3,
        ]);
        $responseOk->assertOk()
            ->assertJsonPath('data.is_available', true);

        // Exceeded quantity
        $responseFail = $this->postJson("/api/products/{$product->id}/check-stock", [
            'quantity' => 10,
        ]);
        $responseFail->assertStatus(422)
            ->assertJsonPath('data.is_available', false);
    }

    public function test_can_update_stock_safely(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Desk Mat',
            'price' => 20.00,
            'stock' => 10,
        ]);

        // Decrement stock
        $decResponse = $this->patchJson("/api/products/{$product->id}/stock", [
            'action' => 'decrement',
            'amount' => 4,
        ]);
        $decResponse->assertOk()
            ->assertJsonPath('data.stock', 6);

        // Cannot reduce below zero
        $invalidDec = $this->patchJson("/api/products/{$product->id}/stock", [
            'action' => 'decrement',
            'amount' => 10,
        ]);
        $invalidDec->assertStatus(422);
    }
}

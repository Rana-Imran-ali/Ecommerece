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
    private \App\Models\User $admin;
    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::create(['name' => 'Electronics']);
        $this->admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $this->adminToken = \Illuminate\Support\Facades\Crypt::encryptString("{$this->admin->id}|" . time());
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

    public function test_unauthenticated_user_cannot_create_or_modify_products(): void
    {
        // Guests cannot create
        $this->postJson('/api/products', [
            'category_id' => $this->category->id,
            'name' => 'Sneaky Product',
            'price' => 10,
            'stock' => 1,
        ])->assertUnauthorized();

        // Regular customer cannot create
        $customer = \App\Models\User::factory()->create(['role' => 'customer']);
        $customerToken = \Illuminate\Support\Facades\Crypt::encryptString("{$customer->id}|" . time());

        $this->withHeader('Authorization', "Bearer {$customerToken}")
            ->postJson('/api/products', [
                'category_id' => $this->category->id,
                'name' => 'Sneaky Product',
                'price' => 10,
                'stock' => 1,
            ])->assertForbidden();
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

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson('/api/products', $payload);

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
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson('/api/products', [
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

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->putJson("/api/products/{$product->id}", [
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

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->deleteJson("/api/products/{$product->id}");

        $response->assertOk();
        $this->assertSoftDeleted('products', ['id' => $product->id]);
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
        $uploadResponse = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/products/{$product->id}/images", [
                'image' => UploadedFile::fake()->create('watch1.jpg', 100, 'image/jpeg'),
            ]);

        $uploadResponse->assertCreated();
        $firstImageId = $uploadResponse->json('data.0.id');

        // Upload second image and designate as primary
        $secondUpload = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/products/{$product->id}/images", [
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
        $decResponse = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->patchJson("/api/products/{$product->id}/stock", [
                'action' => 'decrement',
                'amount' => 4,
            ]);
        $decResponse->assertOk()
            ->assertJsonPath('data.stock', 6);

        // Cannot reduce below zero
        $invalidDec = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->patchJson("/api/products/{$product->id}/stock", [
                'action' => 'decrement',
                'amount' => 10,
            ]);
        $invalidDec->assertStatus(422);
    }

    public function test_initial_stock_creates_inventory_log_on_product_creation(): void
    {
        $payload = [
            'category_id' => $this->category->id,
            'name' => 'Mechanical Numpad',
            'description' => 'Compact numpad with hot-swappable switches',
            'price' => 39.99,
            'stock' => 15,
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson('/api/products', $payload);

        $response->assertCreated();

        $product = Product::where('name', 'Mechanical Numpad')->firstOrFail();

        $this->assertDatabaseHas('inventory_logs', [
            'product_id'      => $product->id,
            'user_id'         => $this->admin->id,
            'type'            => 'stock_in',
            'quantity'        => 15,
            'quantity_before' => 0,
            'quantity_after'  => 15,
            'notes'           => 'Initial stock on product creation',
        ]);
        $this->assertCount(1, $product->inventoryLogs);
    }

    public function test_zero_initial_stock_does_not_create_inventory_log(): void
    {
        $payload = [
            'category_id' => $this->category->id,
            'name' => 'Backordered Mousepad',
            'description' => 'Out of stock on launch',
            'price' => 19.99,
            'stock' => 0,
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson('/api/products', $payload);

        $response->assertCreated();

        $product = Product::where('name', 'Backordered Mousepad')->firstOrFail();

        $this->assertDatabaseMissing('inventory_logs', [
            'product_id' => $product->id,
        ]);
        $this->assertCount(0, $product->inventoryLogs);
    }

    public function test_admin_web_product_creation_creates_single_inventory_log(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.products.store'), [
                'category_id' => $this->category->id,
                'name'        => 'Web Admin Product',
                'description' => 'Created via admin panel',
                'price'       => 49.99,
                'stock'       => 30,
                'status'      => 'active',
            ]);

        $response->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Web Admin Product')->firstOrFail();

        $this->assertDatabaseHas('inventory_logs', [
            'product_id'      => $product->id,
            'user_id'         => $this->admin->id,
            'type'            => 'stock_in',
            'quantity'        => 30,
            'quantity_before' => 0,
            'quantity_after'  => 30,
            'notes'           => 'Initial stock on product creation',
        ]);
        $this->assertCount(1, $product->inventoryLogs);
    }
}

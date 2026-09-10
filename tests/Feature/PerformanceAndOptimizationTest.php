<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceAndOptimizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->adminToken = \Illuminate\Support\Facades\Crypt::encryptString("{$this->admin->id}|" . time());
    }

    public function test_categories_are_cached_and_invalidated_on_mutation(): void
    {
        Cache::flush();

        $cat1 = Category::create(['name' => 'Laptops']);
        $cat2 = Category::create(['name' => 'Smartphones']);

        // First call populates cache
        $res1 = $this->getJson('/api/categories');
        $res1->assertOk();
        $this->assertTrue(Cache::has('categories.all'));
        $this->assertCount(2, $res1->json('data'));

        // Manually alter database directly without model events to test that cached data is returned
        DB::table('categories')->insert([
            'name' => 'Tablets',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Still returns cached 2 categories
        $res2 = $this->getJson('/api/categories');
        $res2->assertOk();
        $this->assertCount(2, $res2->json('data'));

        // Model mutation through API invalidates cache
        $resStore = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson('/api/categories', ['name' => 'Accessories']);
        $resStore->assertCreated();
        $this->assertFalse(Cache::has('categories.all'));

        // Next fetch re-populates with fresh data (including all categories)
        $res3 = $this->getJson('/api/categories');
        $res3->assertOk();
        $this->assertCount(4, $res3->json('data'));
    }

    public function test_product_listing_returns_pagination_metadata_and_eager_loads_cleanly(): void
    {
        $cat = Category::create(['name' => 'Peripherals']);

        for ($i = 1; $i <= 15; $i++) {
            $p = Product::create([
                'category_id' => $cat->id,
                'name' => "Product {$i}",
                'description' => "Description {$i}",
                'price' => 10.00 * $i,
                'stock' => $i,
            ]);

            ProductImage::create([
                'product_id' => $p->id,
                'image' => "products/p{$i}.jpg",
                'is_primary' => true,
            ]);
        }

        // Test page 1 with 5 per page
        $res = $this->getJson('/api/products?page=1&per_page=5');
        $res->assertOk();
        $res->assertJsonPath('meta.current_page', 1);
        $res->assertJsonPath('meta.per_page', 5);
        $res->assertJsonPath('meta.last_page', 3);
        $res->assertJsonPath('meta.total', 15);
        $this->assertCount(5, $res->json('data'));

        // Verify primary image and category are loaded
        $first = $res->json('data.0');
        $this->assertArrayHasKey('primary_image', $first);
        $this->assertArrayHasKey('category', $first);

        // Test page 2
        $resPage2 = $this->getJson('/api/products?page=2&per_page=5');
        $resPage2->assertOk();
        $resPage2->assertJsonPath('meta.current_page', 2);
        $this->assertCount(5, $resPage2->json('data'));
    }

    public function test_product_creation_invalidates_category_cache(): void
    {
        Cache::flush();
        $cat = Category::create(['name' => 'Cameras']);

        $this->getJson('/api/categories')->assertOk();
        $this->assertTrue(Cache::has('categories.all'));

        // Create product
        $res = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson('/api/products', [
                'category_id' => $cat->id,
                'name' => 'DSLR Camera',
                'description' => 'Professional 4K camera',
                'price' => 799.99,
                'stock' => 5,
            ]);
        $res->assertCreated();

        // Categories cache should be cleared
        $this->assertFalse(Cache::has('categories.all'));
    }

    public function test_reviews_listing_eager_loads_reviewer_name(): void
    {
        $cat = Category::create(['name' => 'Audio']);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Noise Cancelling Headphones',
            'price' => 199.99,
            'stock' => 8,
        ]);
        $user = User::factory()->create(['name' => 'Alice']);

        Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'comment' => 'Outstanding sound quality!',
        ]);

        $res = $this->getJson("/api/products/{$product->id}/reviews");
        $res->assertOk();
        $res->assertJsonPath('data.total_reviews', 1);
        $res->assertJsonPath('data.average_rating', 5);
        $res->assertJsonPath('data.reviews.0.user.name', 'Alice');
    }
}

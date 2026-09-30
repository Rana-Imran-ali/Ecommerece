<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\ContactInquiry;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SystemFeatureAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;
    protected Category $category;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Admin
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create Customer
        $this->customerUser = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        // Create Category & Product
        $this->category = Category::create([
            'name' => 'Electronics',
            'description' => 'Electronic devices and gadgets',
        ]);

        $this->product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Pro Wireless Headphones',
            'description' => 'High quality noise cancelling headphones',
            'price' => 199.99,
            'stock' => 50,
            'status' => 'active',
        ]);
    }

    /**
     * Test 1: Web Public Pages Render Correctly
     */
    public function test_public_web_pages_render()
    {
        $pages = [
            '/',
            '/shop',
            '/products',
            '/products/' . $this->product->id,
            '/search?q=Headphones',
            '/categories',
            '/about',
            '/contact',
            '/cart',
            '/checkout',
            '/wishlist',
            '/addresses',
        ];

        foreach ($pages as $url) {
            $response = $this->get($url);
            $this->assertTrue(
                $response->isOk(),
                "Failed to render public page: {$url} (Status: {$response->status()})"
            );
        }
    }

    /**
     * Test 2: Contact Form Submission
     */
    public function test_contact_form_submission()
    {
        $response = $this->post(route('contact.submit'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Product Inquiry',
            'message' => 'Is this headphone waterproof?',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('contact_inquiries', [
            'email' => 'john@example.com',
            'subject' => 'Product Inquiry',
        ]);
    }

    /**
     * Test 3: Authenticated Customer Pages Render Correctly
     */
    public function test_authenticated_customer_pages()
    {
        $this->actingAs($this->customerUser);

        $order = Order::create([
            'user_id' => $this->customerUser->id,
            'total_amount' => 199.99,
            'status' => 'pending',
        ]);

        $pages = [
            '/account',
            '/orders',
            '/orders/' . $order->id,
        ];

        foreach ($pages as $url) {
            $response = $this->get($url);
            $this->assertTrue(
                $response->isOk(),
                "Failed to render customer page: {$url} (Status: {$response->status()})"
            );
        }
    }

    /**
     * Test 4: Customer Address Management (API)
     */
    public function test_address_management_api()
    {
        $this->actingAs($this->customerUser);

        // Create address
        $response = $this->postJson('/api/addresses', [
            'name' => 'Home',
            'phone' => '1234567890',
            'address_line1' => '123 Main St',
            'city' => 'Metropolis',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'USA',
            'is_default' => true,
        ]);
        $response->assertStatus(201);
        $addressId = $response->json('data.id');

        // List addresses
        $listResponse = $this->getJson('/api/addresses');
        $listResponse->assertOk();
        $this->assertCount(1, $listResponse->json('data'));

        // Update address
        $updateResponse = $this->putJson("/api/addresses/{$addressId}", [
            'name' => 'Office',
            'phone' => '1234567890',
            'address_line1' => '456 Tech Ave',
            'city' => 'Metropolis',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'USA',
            'is_default' => true,
        ]);
        $updateResponse->assertOk();

        // Delete address
        $deleteResponse = $this->deleteJson("/api/addresses/{$addressId}");
        $deleteResponse->assertOk();
        $this->assertDatabaseMissing('addresses', ['id' => $addressId]);
    }

    /**
     * Test 5: Wishlist Management (API)
     */
    public function test_wishlist_management_api()
    {
        $this->actingAs($this->customerUser);

        // Add to wishlist
        $addResponse = $this->postJson('/api/wishlist/items', [
            'product_id' => $this->product->id,
        ]);
        $addResponse->assertOk();
        $itemId = $addResponse->json('data.id');

        // List wishlist
        $listResponse = $this->getJson('/api/wishlist');
        $listResponse->assertOk();
        $this->assertEquals(1, $listResponse->json('data.total_items'));

        // Remove item
        $removeResponse = $this->deleteJson("/api/wishlist/items/{$itemId}");
        $removeResponse->assertOk();

        // Clear wishlist
        $this->postJson('/api/wishlist/items', ['product_id' => $this->product->id]);
        $clearResponse = $this->deleteJson('/api/wishlist');
        $clearResponse->assertOk();
    }

    /**
     * Test 6: Cart & Checkout flow (COD)
     */
    public function test_cart_and_checkout_flow()
    {
        $this->actingAs($this->customerUser);

        // Add address
        $address = Address::create([
            'user_id' => $this->customerUser->id,
            'name' => 'Home',
            'phone' => '1234567890',
            'address_line1' => '123 Main St',
            'city' => 'Metropolis',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'USA',
            'is_default' => true,
        ]);

        // Add product to cart
        $cartAdd = $this->postJson('/api/cart/items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);
        $cartAdd->assertOk();

        // View Cart
        $cartView = $this->getJson('/api/cart');
        $cartView->assertOk();

        // Checkout COD
        $checkoutResponse = $this->postJson('/api/orders', [
            'address_id' => $address->id,
            'payment_method' => 'cod',
            'notes' => 'Please leave at door',
        ]);
        $checkoutResponse->assertStatus(201);
        $orderId = $checkoutResponse->json('data.id');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => 'pending',
        ]);

        // Stock was 50, bought 2 -> should be 48
        $this->product->refresh();
        $this->assertEquals(48, $this->product->stock);

        // Cancel order -> should restore stock to 50
        $cancelResponse = $this->patchJson("/api/orders/{$orderId}/cancel");
        $cancelResponse->assertOk();

        $this->product->refresh();
        $this->assertEquals(50, $this->product->stock);
    }

    /**
     * Test 7: Admin Dashboard & Reports
     */
    public function test_admin_dashboard_and_reports()
    {
        $this->actingAs($this->adminUser);

        $dashResponse = $this->get(route('admin.dashboard'));
        $dashResponse->assertOk();

        $salesReport = $this->get(route('admin.reports.sales'));
        $salesReport->assertOk();

        $topProducts = $this->get(route('admin.reports.top-products'));
        $topProducts->assertOk();
    }

    /**
     * Test 8: Admin Categories CRUD
     */
    public function test_admin_categories_crud()
    {
        $this->actingAs($this->adminUser);

        // Index
        $this->get(route('admin.categories.index'))->assertOk();

        // Create
        $this->get(route('admin.categories.create'))->assertOk();
        $storeResponse = $this->post(route('admin.categories.store'), [
            'name' => 'Smart Home',
            'description' => 'Automated home devices',
        ]);
        $storeResponse->assertRedirect(route('admin.categories.index'));
        $cat = Category::where('name', 'Smart Home')->firstOrFail();

        // Edit
        $this->get(route('admin.categories.edit', $cat))->assertOk();
        $updateResponse = $this->put(route('admin.categories.update', $cat), [
            'name' => 'Smart Home & IoT',
            'description' => 'Automated devices and sensors',
        ]);
        $updateResponse->assertRedirect(route('admin.categories.index'));

        // Destroy
        $destroyResponse = $this->delete(route('admin.categories.destroy', $cat));
        $destroyResponse->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $cat->id]);
    }

    /**
     * Test 9: Admin Products CRUD
     */
    public function test_admin_products_crud()
    {
        $this->actingAs($this->adminUser);

        $this->get(route('admin.products.index'))->assertOk();
        $this->get(route('admin.products.create'))->assertOk();

        $storeResponse = $this->post(route('admin.products.store'), [
            'name' => 'Bluetooth Speaker',
            'category_id' => $this->category->id,
            'description' => 'Portable loud speaker',
            'price' => 79.99,
            'stock' => 20,
            'status' => 'active',
        ]);
        $storeResponse->assertRedirect(route('admin.products.index'));

        $newProduct = Product::where('name', 'Bluetooth Speaker')->firstOrFail();
        $this->get(route('admin.products.edit', $newProduct))->assertOk();

        $updateResponse = $this->put(route('admin.products.update', $newProduct), [
            'name' => 'Bluetooth Speaker v2',
            'category_id' => $this->category->id,
            'description' => 'Updated portable speaker',
            'price' => 89.99,
            'stock' => 25,
            'status' => 'active',
        ]);
        $updateResponse->assertRedirect(route('admin.products.edit', $newProduct));

        $deleteResponse = $this->delete(route('admin.products.destroy', $newProduct));
        $deleteResponse->assertRedirect(route('admin.products.index'));
        $this->assertSoftDeleted('products', ['id' => $newProduct->id]);
    }

    /**
     * Test 10: Admin Inventory Adjustment
     */
    public function test_admin_inventory_adjustment()
    {
        $this->actingAs($this->adminUser);

        $this->get(route('admin.inventory.index'))->assertOk();

        $adjustResponse = $this->post(route('admin.inventory.adjust'), [
            'product_id' => $this->product->id,
            'type' => 'stock_in',
            'quantity' => 15,
            'notes' => 'Received new batch',
        ]);
        $adjustResponse->assertSessionHas('success');

        $this->product->refresh();
        $this->assertEquals(65, $this->product->stock);
    }

    /**
     * Test 11: Admin Orders Management & Notifications
     */
    public function test_admin_orders_management()
    {
        Notification::fake();
        $this->actingAs($this->adminUser);

        $order = Order::create([
            'user_id' => $this->customerUser->id,
            'total_amount' => 199.99,
            'status' => 'pending',
        ]);

        // Index & Show
        $this->get(route('admin.orders.index'))->assertOk();
        $this->get(route('admin.orders.show', $order))->assertOk();

        // Update status
        $statusResponse = $this->patch(route('admin.orders.status', $order), [
            'status' => 'processing',
        ]);
        $statusResponse->assertSessionHas('success');
        $order->refresh();
        $this->assertEquals('processing', $order->status);

        // Send approval email
        $approvalResponse = $this->post(route('admin.orders.notify.approval', $order));
        $approvalResponse->assertSessionHas('success');

        // Send delivery date email
        $deliveryResponse = $this->post(route('admin.orders.notify.delivery-date', $order), [
            'expected_delivery_date' => now()->addDays(3)->format('Y-m-d'),
        ]);
        $deliveryResponse->assertSessionHas('success');
    }

    /**
     * Test 12: Admin Payments Management
     */
    public function test_admin_payments_management()
    {
        $this->actingAs($this->adminUser);

        $order = Order::create([
            'user_id' => $this->customerUser->id,
            'total_amount' => 199.99,
            'status' => 'pending',
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 199.99,
            'payment_method' => 'cod',
            'status' => 'pending',
        ]);

        // Payments Index
        $indexResponse = $this->get(route('admin.payments.index'));
        $indexResponse->assertOk();

        // Payments Show
        $showResponse = $this->get(route('admin.payments.show', $payment));
        $showResponse->assertOk();

        // Update Status to completed
        $updateResponse = $this->patch(route('admin.payments.status', $payment), [
            'status' => 'completed',
        ]);
        $updateResponse->assertSessionHas('success');
        $payment->refresh();
        $this->assertEquals('completed', $payment->status);
    }

    /**
     * Test 13: Admin Users & Customers
     */
    public function test_admin_users_and_customers()
    {
        $this->actingAs($this->adminUser);

        // Users
        $this->get(route('admin.users.index'))->assertOk();
        $this->get(route('admin.users.show', $this->customerUser))->assertOk();

        // Customers
        $this->get(route('admin.customers.index'))->assertOk();
        $this->get(route('admin.customers.show', $this->customerUser))->assertOk();

        // Role update
        $roleResponse = $this->patch(route('admin.users.role', $this->customerUser), [
            'role' => 'admin',
        ]);
        $roleResponse->assertSessionHas('success');
        $this->customerUser->refresh();
        $this->assertEquals('admin', $this->customerUser->role);
    }

    /**
     * Test 14: Admin Coupons CRUD
     */
    public function test_admin_coupons_crud()
    {
        $this->actingAs($this->adminUser);

        $this->get(route('admin.coupons.index'))->assertOk();
        $this->get(route('admin.coupons.create'))->assertOk();

        $storeResponse = $this->post(route('admin.coupons.store'), [
            'code' => 'SAVE10',
            'discount_type' => 'percent',
            'discount_percent' => 10,
            'min_order_amount' => 50,
            'max_uses' => 100,
            'expires_at' => now()->addDays(30)->format('Y-m-d H:i:s'),
            'is_active' => 1,
        ]);
        $storeResponse->assertRedirect(route('admin.coupons.index'));

        $coupon = Coupon::where('code', 'SAVE10')->firstOrFail();
        $this->get(route('admin.coupons.edit', $coupon))->assertOk();

        $updateResponse = $this->put(route('admin.coupons.update', $coupon), [
            'code' => 'SAVE15',
            'discount_type' => 'percent',
            'discount_percent' => 15,
            'min_order_amount' => 50,
            'max_uses' => 100,
            'expires_at' => now()->addDays(30)->format('Y-m-d H:i:s'),
            'is_active' => 1,
        ]);
        $updateResponse->assertRedirect(route('admin.coupons.index'));

        $destroyResponse = $this->delete(route('admin.coupons.destroy', $coupon));
        $destroyResponse->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }

    /**
     * Test 15: Admin Reviews Moderation
     */
    public function test_admin_reviews_moderation()
    {
        $this->actingAs($this->adminUser);

        $review = Review::create([
            'user_id' => $this->customerUser->id,
            'product_id' => $this->product->id,
            'rating' => 5,
            'comment' => 'Awesome sound quality!',
            'status' => 'pending',
        ]);

        $this->get(route('admin.reviews.index'))->assertOk();

        // Approve
        $approveResponse = $this->patch(route('admin.reviews.approve', $review));
        $approveResponse->assertSessionHas('success');
        $review->refresh();
        $this->assertEquals('approved', $review->status);

        // Reject
        $rejectResponse = $this->patch(route('admin.reviews.reject', $review));
        $rejectResponse->assertSessionHas('success');
        $review->refresh();
        $this->assertEquals('rejected', $review->status);

        // Delete
        $deleteResponse = $this->delete(route('admin.reviews.destroy', $review));
        $deleteResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    /**
     * Test 16: WhatsApp Webhook
     */
    public function test_whatsapp_webhook()
    {
        config(['whatsapp.verify_token' => 'my_secret_token']);

        // Verify challenge
        $verifyResponse = $this->get('/api/whatsapp/webhook?hub_mode=subscribe&hub_verify_token=my_secret_token&hub_challenge=123456');
        $verifyResponse->assertOk();
        $this->assertEquals('123456', $verifyResponse->getContent());

        // Invalid challenge token
        $failVerify = $this->get('/api/whatsapp/webhook?hub_mode=subscribe&hub_verify_token=wrong_token&hub_challenge=123456');
        $failVerify->assertStatus(403);
    }
}

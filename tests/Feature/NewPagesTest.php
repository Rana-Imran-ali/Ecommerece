<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render_successfully(): void
    {
        $this->get('/shop')->assertOk();
        $this->get('/search')->assertOk();
        $this->get('/about')->assertOk();
        $this->get('/contact')->assertOk();
    }

    public function test_contact_form_submission(): void
    {
        $response = $this->post('/contact', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'Order Inquiry',
            'message' => 'Hello, I have a question about delivery.',
        ]);

        $response->assertSessionHas('success');
    }

    public function test_admin_payments_and_users_pages_require_admin(): void
    {
        // Unauthenticated
        $this->get('/admin/payments')->assertRedirect('/login');
        $this->get('/admin/users')->assertRedirect('/login');

        // Customer cannot access
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get('/admin/payments')->assertForbidden();
        $this->actingAs($customer)->get('/admin/users')->assertForbidden();

        // Admin can access
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin/payments')->assertOk();
        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/categories/create')->assertOk();
        $this->actingAs($admin)->get('/admin/coupons/create')->assertOk();
    }

    public function test_address_street_accessor(): void
    {
        $address = new \App\Models\Address([
            'address_line1' => '123 Market St',
            'address_line2' => 'Suite 400',
        ]);

        $this->assertEquals('123 Market St, Suite 400', $address->street);
    }
}

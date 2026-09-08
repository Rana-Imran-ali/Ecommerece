<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/test-layout', function () {
            return view('layouts.app');
        });
    }

    public function test_navbar_renders_guest_navigation_items(): void
    {
        $response = $this->get('/test-layout');

        $response->assertOk()
            ->assertSee('Home')
            ->assertSee('Products')
            ->assertSee('Categories')
            ->assertSee('Wishlist')
            ->assertSee('Cart')
            ->assertSee('Login')
            ->assertSee('Register')
            ->assertDontSee('Logout');
    }

    public function test_navbar_renders_authenticated_user_navigation(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->actingAs($user)->get('/test-layout');

        $response->assertOk()
            ->assertSee('John Doe')
            ->assertSee('Profile')
            ->assertSee('Logout')
            ->assertDontSee('Register');
    }
}

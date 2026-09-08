<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Coupon::firstOrCreate(
            ['code' => 'WELCOME10'],
            [
                'discount_percent' => 10.00,
                'max_discount' => 50.00,
                'min_order_amount' => 50.00,
                'is_active' => true,
            ]
        );

        Coupon::firstOrCreate(
            ['code' => 'SUPER20'],
            [
                'discount_percent' => 20.00,
                'max_discount' => 100.00,
                'min_order_amount' => 150.00,
                'is_active' => true,
            ]
        );

        Coupon::firstOrCreate(
            ['code' => 'FLAT50'],
            [
                'discount_percent' => 50.00,
                'max_discount' => 200.00,
                'min_order_amount' => 300.00,
                'is_active' => true,
            ]
        );
    }
}

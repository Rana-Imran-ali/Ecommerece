<?php

namespace Database\Seeders;

use App\Models\CouponUsage;
use Illuminate\Database\Seeder;

class CouponUsageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CouponUsage::firstOrCreate([
            'coupon_id' => 1,
            'user_id' => 1,
            'order_id' => 1,
        ]);
    }
}

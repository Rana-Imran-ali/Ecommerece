<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        Order::create([
            'user_id' => 1,
            'address_id' => 1,
            'status' => 'pending',
            'total_amount' => 1499.98,
        ]);
    }
}
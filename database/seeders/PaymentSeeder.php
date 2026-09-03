<?php

namespace Database\Seeders;

use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        Payment::create([
            'order_id' => 1,
            'payment_method' => 'cod',
            'amount' => 1499.98,
            'status' => 'pending',
        ]);
    }
}
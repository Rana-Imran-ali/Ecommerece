<?php

namespace Database\Seeders;

use App\Models\Address;
use Illuminate\Database\Seeder;

class AddressSeeder extends Seeder
{
    public function run(): void
    {
        Address::create([
            'user_id' => 1,
            'name' => 'Test User',
            'phone' => '03001234567',
            'address_line1' => 'Main Boulevard',
            'address_line2' => 'House 123',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'postal_code' => '54000',
            'country' => 'Pakistan',
            'is_default' => true,
        ]);
    }
}
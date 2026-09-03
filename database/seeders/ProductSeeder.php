<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::firstOrCreate(
            ['id' => 1],
            [
                'category_id' => 1,
                'name' => 'iPhone 15',
                'description' => 'Latest Apple iPhone 15 with A16 Bionic chip',
                'price' => 799.99,
                'stock' => 50,
            ]
        );

        Product::firstOrCreate(
            ['id' => 2],
            [
                'category_id' => 1,
                'name' => 'Samsung Galaxy S24',
                'description' => 'Galaxy AI powered flagship phone',
                'price' => 699.99,
                'stock' => 40,
            ]
        );

        Product::firstOrCreate(
            ['id' => 3],
            [
                'category_id' => 2,
                'name' => 'Cotton T-Shirt',
                'description' => '100% premium combed cotton t-shirt',
                'price' => 29.99,
                'stock' => 100,
            ]
        );
    }
}

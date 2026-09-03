<?php

namespace Database\Seeders;

use App\Models\ProductImage;
use Illuminate\Database\Seeder;

class ProductImageSeeder extends Seeder
{
    public function run(): void
    {
        ProductImage::create([
            'product_id' => 1,
            'image' => 'products/iphone-15.jpg',
            'is_primary' => true,
        ]);

        ProductImage::create([
            'product_id' => 2,
            'image' => 'products/samsung-s24.jpg',
            'is_primary' => true,
        ]);

        ProductImage::create([
            'product_id' => 3,
            'image' => 'products/cotton-tshirt.jpg',
            'is_primary' => true,
        ]);
    }
}
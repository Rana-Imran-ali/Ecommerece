<?php

namespace Database\Seeders;

use App\Models\WishlistItem;
use Illuminate\Database\Seeder;

class WishlistItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        WishlistItem::firstOrCreate([
            'wishlist_id' => 1,
            'product_id' => 3,
        ]);
    }
}

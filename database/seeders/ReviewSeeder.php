<?php

namespace Database\Seeders;

use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Review::firstOrCreate(
            ['user_id' => 1, 'product_id' => 1],
            [
                'rating' => 5,
                'comment' => 'Exceptional build quality and lightning fast performance!',
            ]
        );

        Review::firstOrCreate(
            ['user_id' => 1, 'product_id' => 2],
            [
                'rating' => 4,
                'comment' => 'Great battery life and gorgeous display. Highly recommended.',
            ]
        );
    }
}

<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Synchronize parent products.stock from active product_variants.
     */
    public function up(): void
    {
        Product::whereHas('variants')->chunkById(100, function ($products) {
            foreach ($products as $product) {
                $product->syncStockFromVariants();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: stock recalculation is non-destructive
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a composite unique constraint on wishlist_items to prevent duplicate entries
     * at the database level — the final safeguard against race-condition duplicates.
     *
     * The constraint covers (wishlist_id, product_id, product_variant_id).
     * MySQL/MariaDB treat multiple NULL values in a unique index as distinct rows,
     * so null product_variant_id values are correctly deduplicated by the combination
     * of wishlist_id + product_id alone (standard behaviour).
     */
    public function up(): void
    {
        Schema::table('wishlist_items', function (Blueprint $table) {
            $table->unique(
                ['wishlist_id', 'product_id', 'product_variant_id'],
                'wishlist_items_unique_product'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wishlist_items', function (Blueprint $table) {
            $table->dropUnique('wishlist_items_unique_product');
        });
    }
};

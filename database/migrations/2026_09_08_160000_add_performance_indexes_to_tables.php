<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('price', 'products_price_index');
            $table->index('stock', 'products_stock_index');
            $table->index('name', 'products_name_index');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->index(['product_id', 'is_primary'], 'product_images_product_primary_index');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index('product_id', 'reviews_product_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_product_id_index');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropIndex('product_images_product_primary_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_price_index');
            $table->dropIndex('products_stock_index');
            $table->dropIndex('products_name_index');
        });
    }
};

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
        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->unique(['coupon_id', 'user_id'], 'coupon_usages_coupon_user_unique');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->unique(['user_id', 'product_id'], 'reviews_user_product_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->dropUnique('coupon_usages_coupon_user_unique');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_user_product_unique');
        });
    }
};

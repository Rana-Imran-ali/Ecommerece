<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add expiry date, maximum global usage limit, and discount type to coupons.
     *
     * discount_type = 'percent' → discount_percent % of order subtotal (existing behaviour)
     * discount_type = 'fixed'   → flat discount_amount deducted from subtotal
     */
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->string('discount_type', 10)->default('percent')->after('code'); // 'percent' | 'fixed'
            $table->decimal('discount_amount', 10, 2)->nullable()->after('discount_percent'); // used when discount_type = 'fixed'
            $table->unsignedInteger('max_uses')->nullable()->after('min_order_amount');       // null = unlimited
            $table->timestamp('expires_at')->nullable()->after('max_uses');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_amount', 'max_uses', 'expires_at']);
        });
    }
};

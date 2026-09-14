<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a DB-level default of 0.00 to discount_percent so the column
     * is never rejected by MySQL when the value is omitted at the application
     * layer. This is non-destructive and does not drop or alter existing rows.
     */
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->default(0)->change();
        });
    }

    /**
     * Revert: remove the default (restore column to NOT NULL, no default).
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->change();
        });
    }
};

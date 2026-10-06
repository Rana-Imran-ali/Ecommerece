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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            // AUDIT INTEGRITY: restrictOnDelete() — an order row cannot be hard-deleted
            // while its items exist, preserving the complete history of cancelled orders.
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            // AUDIT INTEGRITY: nullOnDelete() — if a product is force-deleted from the
            // catalogue, the order_item row survives with product_id = null. The
            // `product_name` snapshot column (added in a later migration) stores the
            // product name at purchase time, keeping price/qty auditable forever.
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

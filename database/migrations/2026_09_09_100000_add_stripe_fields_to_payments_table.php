<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Stripe Checkout Session and PaymentIntent tracking columns to payments table.
     * These are nullable so all existing COD/bank_transfer payment records are unaffected.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Stripe Checkout Session ID (cs_test_xxx) – created when user initiates card payment
            $table->string('stripe_session_id')->nullable()->unique()->after('status')
                  ->comment('Stripe Checkout Session ID');

            // Stripe PaymentIntent ID (pi_xxx) – populated by webhook after payment succeeds
            $table->string('stripe_payment_intent_id')->nullable()->after('stripe_session_id')
                  ->comment('Stripe PaymentIntent ID, filled by webhook');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['stripe_session_id', 'stripe_payment_intent_id']);
        });
    }
};

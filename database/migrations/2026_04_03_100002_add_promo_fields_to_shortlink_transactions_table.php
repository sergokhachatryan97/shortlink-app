<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shortlink_transactions', function (Blueprint $table) {
            $table->foreignId('promo_code_id')->nullable()->after('payment_kind')->constrained('promo_codes')->nullOnDelete();
            $table->decimal('subscription_gross_amount', 12, 2)->nullable()->after('promo_code_id');
            $table->decimal('subscription_discount_amount', 12, 2)->nullable()->after('subscription_gross_amount');
        });
    }

    public function down(): void
    {
        Schema::table('shortlink_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promo_code_id');
            $table->dropColumn(['subscription_gross_amount', 'subscription_discount_amount']);
        });
    }
};

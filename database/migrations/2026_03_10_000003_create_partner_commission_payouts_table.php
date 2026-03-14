<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_commission_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('partner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider', 50);
            $table->decimal('source_amount', 12, 2);
            $table->decimal('commission_percent', 5, 2);
            $table->decimal('commission_amount', 12, 2);
            $table->string('currency', 20)->default('USDT');
            $table->string('network', 50)->nullable();
            $table->string('wallet_address');
            $table->string('provider_transaction_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->string('source_type', 50)->nullable();
            $table->string('source_id')->nullable();
            $table->timestamps();

            $table->index(['partner_user_id', 'status']);
            $table->index(['status', 'provider']);
            $table->index(['source_user_id', 'source_type', 'source_id'], 'pco_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_commission_payouts');
    }
};

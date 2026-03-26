<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_client_id')->constrained('external_clients')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('external_services')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('external_categories')->restrictOnDelete();
            $table->string('link', 2048)->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('charge', 14, 4)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->string('status', 32)->default('pending');
            $table->string('provider_order_id', 128)->nullable();
            $table->string('external_reference', 191)->nullable();
            $table->string('idempotency_key', 191)->nullable();
            $table->unsignedInteger('start_count')->nullable();
            $table->unsignedInteger('remains')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->json('custom_comments')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['external_client_id', 'status']);
            $table->index(['service_id']);
            $table->index(['category_id']);
            $table->unique(['external_client_id', 'idempotency_key']);
            $table->unique(['external_client_id', 'external_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('api_key_hash', 64)->unique();
            $table->boolean('is_active')->default(true);
            $table->decimal('balance', 14, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->string('callback_url', 2048)->nullable();
            $table->string('webhook_secret')->nullable();
            $table->json('allowed_ips')->nullable();
            $table->unsignedInteger('rate_limit_per_minute')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_clients');
    }
};

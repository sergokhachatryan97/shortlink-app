<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shortlink_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 64)->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('USD');
            $table->string('status', 32)->default('pending');
            $table->string('identifier', 128)->nullable();
            $table->integer('count');
            $table->string('url', 2048)->nullable();
            $table->string('provider_ref', 128)->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shortlink_transactions');
    }
};

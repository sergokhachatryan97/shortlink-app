<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shortlink_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_url', 2048);
            $table->string('short_url', 512);
            $table->integer('batch_index')->default(0); // order within a generation batch
            $table->string('batch_id', 64)->nullable(); // group links from same generation
            $table->timestamps();
            $table->index(['user_id', 'user_subscription_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shortlink_links');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utm_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('original_url', 2048);
            $table->string('utm_source', 255);
            $table->string('utm_medium', 255);
            $table->string('utm_campaign', 255);
            $table->string('utm_content', 255)->nullable();
            $table->string('utm_term', 255)->nullable();
            $table->text('final_url');
            $table->string('short_url', 512)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'utm_campaign']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utm_links');
    }
};

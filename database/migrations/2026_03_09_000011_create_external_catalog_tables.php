<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('link_driver', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('external_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('external_categories')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_external_available')->default(true);
            $table->unsignedInteger('min_quantity')->default(1);
            $table->unsignedInteger('max_quantity')->default(1000000);
            $table->decimal('rate', 14, 4)->default(0);
            $table->string('unit', 20)->default('1000');
            $table->boolean('requires_link')->default(true);
            $table->boolean('requires_quantity')->default(true);
            $table->boolean('requires_custom_comments')->default(false);
            $table->json('rules')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'is_external_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_services');
        Schema::dropIfExists('external_categories');
    }
};

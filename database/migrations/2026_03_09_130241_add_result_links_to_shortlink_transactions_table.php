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
        Schema::table('shortlink_transactions', function (Blueprint $table) {
            $table->json('result_links')->nullable()->after('provider_ref');
        });
    }

    public function down(): void
    {
        Schema::table('shortlink_transactions', function (Blueprint $table) {
            $table->dropColumn('result_links');
        });
    }
};

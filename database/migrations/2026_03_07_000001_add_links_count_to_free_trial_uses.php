<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shortlink_free_trial_uses', function (Blueprint $table) {
            $table->unsignedInteger('links_count')->default(1)->after('ip_address');
        });

        // Existing rows: treat as exhausted (we don't know how many links they used)
        DB::table('shortlink_free_trial_uses')->update(['links_count' => 50]);
    }

    public function down(): void
    {
        Schema::table('shortlink_free_trial_uses', function (Blueprint $table) {
            $table->dropColumn('links_count');
        });
    }
};

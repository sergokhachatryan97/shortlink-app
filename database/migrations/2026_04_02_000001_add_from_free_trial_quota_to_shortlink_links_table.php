<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shortlink_links', function (Blueprint $table) {
            $table->boolean('from_free_trial_quota')->default(false)->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('shortlink_links', function (Blueprint $table) {
            $table->dropColumn('from_free_trial_quota');
        });
    }
};

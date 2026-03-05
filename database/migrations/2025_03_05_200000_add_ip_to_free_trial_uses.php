<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shortlink_free_trial_uses', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('identifier')->index();
        });
    }

    public function down(): void
    {
        Schema::table('shortlink_free_trial_uses', function (Blueprint $table) {
            $table->dropIndex(['ip_address']);
            $table->dropColumn('ip_address');
        });
    }
};

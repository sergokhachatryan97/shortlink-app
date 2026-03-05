<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shortlink_free_trial_uses', function (Blueprint $table) {
            $table->string('identifier', 128)->nullable()->after('id');
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            \Illuminate\Support\Facades\DB::statement(
                "UPDATE shortlink_free_trial_uses SET identifier = CONCAT('ip:', ip_address)"
            );
        } elseif ($driver === 'sqlite') {
            \Illuminate\Support\Facades\DB::statement(
                "UPDATE shortlink_free_trial_uses SET identifier = 'ip:' || ip_address"
            );
        }

        Schema::table('shortlink_free_trial_uses', function (Blueprint $table) {
            $table->dropColumn('ip_address');
            $table->string('identifier', 128)->nullable(false)->change();
            $table->index('identifier');
        });
    }

    public function down(): void
    {
        Schema::table('shortlink_free_trial_uses', function (Blueprint $table) {
            $table->dropIndex(['identifier']);
            $table->string('ip_address', 45)->nullable()->after('id');
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            \Illuminate\Support\Facades\DB::statement(
                "UPDATE shortlink_free_trial_uses SET ip_address = SUBSTRING(identifier, 5) WHERE identifier LIKE 'ip:%'"
            );
        } elseif ($driver === 'sqlite') {
            \Illuminate\Support\Facades\DB::statement(
                "UPDATE shortlink_free_trial_uses SET ip_address = SUBSTR(identifier, 5) WHERE identifier LIKE 'ip:%'"
            );
        }

        Schema::table('shortlink_free_trial_uses', function (Blueprint $table) {
            $table->dropColumn('identifier');
        });
    }
};

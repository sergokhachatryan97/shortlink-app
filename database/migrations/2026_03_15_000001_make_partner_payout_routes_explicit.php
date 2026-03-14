<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_payout_settings', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'provider']);
        });

        DB::table('partner_payout_settings')
            ->whereNull('currency')
            ->orWhere('currency', '')
            ->update(['currency' => 'USDT']);

        DB::table('partner_payout_settings')
            ->whereNull('network')
            ->orWhere('network', '')
            ->update(['network' => 'TRC20']);

        Schema::table('partner_payout_settings', function (Blueprint $table) {
            $table->unique(['user_id', 'provider', 'currency', 'network']);
        });
    }

    public function down(): void
    {
        Schema::table('partner_payout_settings', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'provider', 'currency', 'network']);
            $table->unique(['user_id', 'provider']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('payout_provider', 50)->nullable()->after('referral_code')->comment('Admin-set: which provider to use for partner payouts (heleket/coinrush)');
        });

        Schema::table('partner_commission_payouts', function (Blueprint $table) {
            $table->string('source_provider', 50)->nullable()->after('provider')->comment('Where the referred user paid (heleket/coinrush)');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('payout_provider');
        });

        Schema::table('partner_commission_payouts', function (Blueprint $table) {
            $table->dropColumn('source_provider');
        });
    }
};

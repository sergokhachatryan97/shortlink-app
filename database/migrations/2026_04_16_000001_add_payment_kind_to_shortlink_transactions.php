<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shortlink_transactions', function (Blueprint $table) {
            $table->string('payment_kind', 40)->nullable()->after('provider_ref');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("UPDATE shortlink_transactions SET payment_kind = 'balance_topup' WHERE payment_kind IS NULL AND order_id LIKE 'bal-%' AND identifier LIKE 'user:%'");
            DB::statement("UPDATE shortlink_transactions SET payment_kind = 'shortlink_payment' WHERE payment_kind IS NULL AND order_id LIKE 'sl-%' AND `count` > 0 AND url IS NOT NULL AND url != ''");
            DB::statement("UPDATE shortlink_transactions SET payment_kind = 'subscription' WHERE payment_kind IS NULL AND (order_id LIKE 'sub-%' OR provider_ref LIKE 'subscription:%')");
            DB::statement("UPDATE shortlink_transactions SET payment_kind = 'subscription_upgrade' WHERE payment_kind IS NULL AND (order_id LIKE 'upg-%' OR provider_ref LIKE 'upgrade:%')");
        } else {
            DB::table('shortlink_transactions')
                ->whereNull('payment_kind')
                ->where('order_id', 'like', 'bal-%')
                ->where('identifier', 'like', 'user:%')
                ->update(['payment_kind' => 'balance_topup']);

            DB::table('shortlink_transactions')
                ->whereNull('payment_kind')
                ->where('order_id', 'like', 'sl-%')
                ->where('count', '>', 0)
                ->whereNotNull('url')
                ->where('url', '!=', '')
                ->update(['payment_kind' => 'shortlink_payment']);

            DB::table('shortlink_transactions')
                ->whereNull('payment_kind')
                ->where(function ($q) {
                    $q->where('order_id', 'like', 'sub-%')
                        ->orWhere('provider_ref', 'like', 'subscription:%');
                })
                ->update(['payment_kind' => 'subscription']);

            DB::table('shortlink_transactions')
                ->whereNull('payment_kind')
                ->where(function ($q) {
                    $q->where('order_id', 'like', 'upg-%')
                        ->orWhere('provider_ref', 'like', 'upgrade:%');
                })
                ->update(['payment_kind' => 'subscription_upgrade']);
        }
    }

    public function down(): void
    {
        Schema::table('shortlink_transactions', function (Blueprint $table) {
            $table->dropColumn('payment_kind');
        });
    }
};

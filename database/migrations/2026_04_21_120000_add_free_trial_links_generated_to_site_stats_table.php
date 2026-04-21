<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_stats', function (Blueprint $table) {
            $table->unsignedBigInteger('free_trial_links_generated')->default(0)->after('lifetime_links_generated');
        });

        $trial = (int) DB::table('shortlink_free_trial_uses')->sum('links_count');
        DB::table('site_stats')->where('id', 1)->update(['free_trial_links_generated' => $trial]);
    }

    public function down(): void
    {
        Schema::table('site_stats', function (Blueprint $table) {
            $table->dropColumn('free_trial_links_generated');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_stats', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('lifetime_links_generated')->default(0);
        });

        $initial = (int) DB::table('shortlink_links')->count();
        DB::table('site_stats')->insert([
            'id' => 1,
            'lifetime_links_generated' => $initial,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_stats');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('telegram_id')->nullable()->unique()->after('google_id');
            $table->string('avatar')->nullable()->after('telegram_id');
            $table->decimal('balance', 12, 2)->default(0)->after('avatar');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'telegram_id', 'avatar', 'balance']);
        });
    }
};

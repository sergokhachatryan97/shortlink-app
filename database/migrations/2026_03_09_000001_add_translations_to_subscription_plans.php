<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
            $table->json('description_translations')->nullable()->after('description');
        });

        // Migrate existing name/description into en translation
        $plans = DB::table('subscription_plans')->get();
        foreach ($plans as $plan) {
            DB::table('subscription_plans')->where('id', $plan->id)->update([
                'name_translations' => json_encode(['en' => $plan->name ?? '']),
                'description_translations' => json_encode(['en' => $plan->description ?? '']),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['name_translations', 'description_translations']);
        });
    }
};

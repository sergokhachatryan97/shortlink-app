<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class WhiteLabelPlanSeeder extends Seeder
{
    public function run(): void
    {
        SubscriptionPlan::firstOrCreate(
            ['slug' => 'white-label'],
            [
                'name' => 'White-Label API',
                'name_translations' => [
                    'en' => 'White-Label API',
                    'ru' => 'White-Label API',
                    'zh' => 'White-Label API',
                ],
                'description' => 'Full white-label API access with custom branding',
                'description_translations' => [
                    'en' => 'Full white-label API access with custom branding',
                    'ru' => 'Полный доступ к White-Label API с вашим брендингом',
                    'zh' => 'White-Label API 完全访问权限',
                ],
                'price_usd' => 1999.00,
                'links_limit' => 0,
                'daily_links_limit' => null,
                'duration_days' => 365,
                'is_active' => true,
                'sort_order' => 5,
            ]
        );
    }
}

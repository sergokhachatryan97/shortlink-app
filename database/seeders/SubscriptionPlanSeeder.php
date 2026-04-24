<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => '5,000 links per 30 days, stored until subscription ends',
                'price_usd' => 4.99,
                'links_limit' => 5000,
                'daily_links_limit' => null,
                'duration_days' => 30,
                'sort_order' => 1,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => '20,000 links per 30 days, stored until subscription ends',
                'price_usd' => 14.99,
                'links_limit' => 20000,
                'daily_links_limit' => null,
                'duration_days' => 30,
                'sort_order' => 2,
            ],
            [
                'name' => 'VIP',
                'slug' => 'vip',
                'description' => 'Unlimited subscription links for one year — up to 50,000 per calendar day',
                'price_usd' => 149.00,
                'links_limit' => 0,
                'daily_links_limit' => 50000,
                'duration_days' => 365,
                'sort_order' => 3,
            ],
            [
                'name' => 'UNLIMITED',
                'slug' => 'unlimited',
                'description' => 'Unlimited subscription links for one year — no daily cap',
                'price_usd' => 999.00,
                'links_limit' => 0,
                'daily_links_limit' => null,
                'duration_days' => 365,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}

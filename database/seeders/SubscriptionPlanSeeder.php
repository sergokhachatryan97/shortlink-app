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
                'description' => '100000 links per month, stored until subscription ends',
                'price_usd' => 4.99,
                'links_limit' => 50000,
                'duration_days' => 30,
                'sort_order' => 1,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => '2000 links per month, stored until subscription ends',
                'price_usd' => 14.99,
                'links_limit' => 2000,
                'duration_days' => 30,
                'sort_order' => 2,
            ],
            [
                'name' => 'VIP',
                'slug' => 'vip',
                'description' => 'Unlimited links per year',
                'price_usd' => 200,
                'links_limit' => 0,
                'duration_days' => 365,
                'sort_order' => 3,
            ],
        ];

        // Rename legacy 'unlimited' plan to 'vip' if it exists
        SubscriptionPlan::where('slug', 'unlimited')->update(['name' => 'VIP', 'slug' => 'vip', 'description' => 'Unlimited links per year', 'price_usd' => 200, 'links_limit' => 0, 'duration_days' => 365, 'sort_order' => 3]);

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}

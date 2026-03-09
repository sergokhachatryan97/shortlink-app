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
                'description' => '500 links per month, stored until subscription ends',
                'price_usd' => 4.99,
                'links_limit' => 500,
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
                'name' => 'Unlimited',
                'slug' => 'unlimited',
                'description' => 'Unlimited links per month',
                'price_usd' => 200,
                'links_limit' => 0,
                'duration_days' => 30,
                'sort_order' => 3,
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

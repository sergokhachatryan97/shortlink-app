<?php

namespace Tests\Feature;

use App\Models\ShortlinkLink;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VipDailyLinkLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_unlimited_plan_with_daily_cap_blocks_when_subscription_links_exhaust_day(): void
    {
        $plan = SubscriptionPlan::query()->create([
            'name' => 'VIP Test',
            'slug' => 'vip-test',
            'description' => 'test',
            'price_usd' => 1,
            'links_limit' => 0,
            'daily_links_limit' => 3,
            'duration_days' => 365,
            'is_active' => true,
            'sort_order' => 99,
        ]);

        $user = User::factory()->create(['balance' => 100]);
        $sub = UserSubscription::query()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addYear(),
            'status' => 'active',
        ]);

        DB::table('shortlink_free_trial_uses')->insert([
            'identifier' => 'user:'.$user->id,
            'ip_address' => '127.0.0.1',
            'links_count' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (range(1, 3) as $i) {
            ShortlinkLink::query()->create([
                'user_id' => $user->id,
                'user_subscription_id' => $sub->id,
                'original_url' => 'https://example.com',
                'short_url' => 'https://short.test/'.$i,
                'batch_index' => $i,
                'batch_id' => 'batch-test',
            ]);
        }

        $this->actingAs($user)->postJson(route('shortlink.generate'), [
            'url' => 'https://example.com',
            'count' => 1,
        ])->assertStatus(422)
            ->assertJsonPath('error', 'daily_limit');
    }

    public function test_unlimited_plan_without_daily_cap_allows_generation(): void
    {
        $plan = SubscriptionPlan::query()->create([
            'name' => 'UNLIMITED Test',
            'slug' => 'unlimited-test',
            'description' => 'test',
            'price_usd' => 1,
            'links_limit' => 0,
            'daily_links_limit' => null,
            'duration_days' => 365,
            'is_active' => true,
            'sort_order' => 100,
        ]);

        $user = User::factory()->create(['balance' => 100]);
        UserSubscription::query()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addYear(),
            'status' => 'active',
        ]);

        DB::table('shortlink_free_trial_uses')->insert([
            'identifier' => 'user:'.$user->id,
            'ip_address' => '127.0.0.1',
            'links_count' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake(['*shorten*' => Http::response(['https://s.unlim.test/1'], 200)]);

        $response = $this->actingAs($user)->postJson(route('shortlink.generate'), [
            'url' => 'https://example.com',
            'count' => 1,
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
    }
}

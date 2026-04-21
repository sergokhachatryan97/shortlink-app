<?php

namespace Tests\Feature;

use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\ShortlinkTransaction;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPromoCodeTest extends TestCase
{
    use RefreshDatabase;

    private function makePlan(string $slug, float $price, int $sort = 1): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => ucfirst($slug).' Plan',
            'slug' => $slug,
            'description' => 'Test',
            'price_usd' => $price,
            'links_limit' => 100,
            'duration_days' => 30,
            'is_active' => true,
            'sort_order' => $sort,
        ]);
    }

    public function test_validate_promo_percent_discount_is_case_insensitive(): void
    {
        $plan = $this->makePlan('promo-test-a', 20.00);
        PromoCode::create([
            'code' => 'SAVE10',
            'discount_type' => PromoCode::DISCOUNT_PERCENT,
            'discount_value' => 10,
            'expires_at' => null,
            'max_uses' => null,
            'once_per_user' => false,
            'first_purchase_only' => false,
            'applies_to_plan_ids' => null,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['balance' => 100]);

        $this->actingAs($user)
            ->postJson(route('subscription.promo.validate'), [
                'plan_id' => $plan->id,
                'promo_code' => 'save10',
                'context' => PromoCodeUsage::CONTEXT_PURCHASE,
            ])
            ->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('original_amount', 20)
            ->assertJsonPath('discount_amount', 2)
            ->assertJsonPath('final_amount', 18);
    }

    public function test_purchase_with_promo_records_usage_and_transaction_audit(): void
    {
        $plan = $this->makePlan('promo-test-b', 50.00);
        $promo = PromoCode::create([
            'code' => 'HALF',
            'discount_type' => PromoCode::DISCOUNT_FIXED,
            'discount_value' => 20,
            'expires_at' => null,
            'max_uses' => null,
            'once_per_user' => false,
            'first_purchase_only' => false,
            'applies_to_plan_ids' => null,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['balance' => 100]);

        $this->actingAs($user)
            ->post(route('subscription.purchase'), [
                'plan_id' => $plan->id,
                'promo_code' => 'HALF',
            ])
            ->assertRedirect(route('subscription.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'balance' => '70.000000',
        ]);

        $this->assertDatabaseCount('promo_code_usages', 1);
        $this->assertDatabaseHas('promo_code_usages', [
            'promo_code_id' => $promo->id,
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'context' => PromoCodeUsage::CONTEXT_PURCHASE,
        ]);

        $tx = ShortlinkTransaction::query()->where('payment_kind', ShortlinkTransaction::KIND_SUBSCRIPTION)->first();
        $this->assertNotNull($tx);
        $this->assertSame($promo->id, (int) $tx->promo_code_id);
        $this->assertEquals('-30.00', (string) $tx->amount);
        $this->assertEquals('50.00', (string) $tx->subscription_gross_amount);
        $this->assertEquals('20.00', (string) $tx->subscription_discount_amount);
    }

    public function test_promo_max_uses_blocks_second_redemption(): void
    {
        $plan = $this->makePlan('promo-test-c', 10.00);
        PromoCode::create([
            'code' => 'ONCEGLOB',
            'discount_type' => PromoCode::DISCOUNT_FIXED,
            'discount_value' => 1,
            'expires_at' => null,
            'max_uses' => 1,
            'once_per_user' => false,
            'first_purchase_only' => false,
            'applies_to_plan_ids' => null,
            'is_active' => true,
        ]);

        $u1 = User::factory()->create(['balance' => 50]);
        $u2 = User::factory()->create(['balance' => 50]);

        $this->actingAs($u1)->post(route('subscription.purchase'), [
            'plan_id' => $plan->id,
            'promo_code' => 'ONCEGLOB',
        ])->assertRedirect();

        $this->actingAs($u2)->post(route('subscription.purchase'), [
            'plan_id' => $plan->id,
            'promo_code' => 'ONCEGLOB',
        ])->assertSessionHasErrors('promo_code');
    }

    public function test_promo_restricted_to_plan_is_rejected_for_other_plan(): void
    {
        $planA = $this->makePlan('promo-only-a', 10.00, 1);
        $planB = $this->makePlan('promo-only-b', 15.00, 2);
        PromoCode::create([
            'code' => 'ONLYA',
            'discount_type' => PromoCode::DISCOUNT_PERCENT,
            'discount_value' => 50,
            'expires_at' => null,
            'max_uses' => null,
            'once_per_user' => false,
            'first_purchase_only' => false,
            'applies_to_plan_ids' => [(int) $planA->id],
            'is_active' => true,
        ]);

        $user = User::factory()->create(['balance' => 50]);

        $this->actingAs($user)
            ->postJson(route('subscription.promo.validate'), [
                'plan_id' => $planB->id,
                'promo_code' => 'ONLYA',
                'context' => PromoCodeUsage::CONTEXT_PURCHASE,
            ])
            ->assertOk()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('error_code', 'plan_not_applicable');
    }

    public function test_first_purchase_only_rejects_when_user_had_subscription(): void
    {
        $plan = $this->makePlan('promo-fp', 25.00);
        PromoCode::create([
            'code' => 'FIRST',
            'discount_type' => PromoCode::DISCOUNT_PERCENT,
            'discount_value' => 100,
            'expires_at' => null,
            'max_uses' => null,
            'once_per_user' => false,
            'first_purchase_only' => true,
            'applies_to_plan_ids' => null,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['balance' => 100]);
        UserSubscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'starts_at' => now()->subYear(),
            'ends_at' => now()->subMonth(),
            'status' => 'expired',
            'provider_ref' => 'balance',
        ]);

        $this->actingAs($user)
            ->postJson(route('subscription.promo.validate'), [
                'plan_id' => $plan->id,
                'promo_code' => 'FIRST',
                'context' => PromoCodeUsage::CONTEXT_PURCHASE,
            ])
            ->assertOk()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('error_code', 'first_purchase_only');
    }
}

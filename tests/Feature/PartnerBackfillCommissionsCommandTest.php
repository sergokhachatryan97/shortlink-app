<?php

namespace Tests\Feature;

use App\Models\PartnerCommissionPayout;
use App\Models\ShortlinkTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerBackfillCommissionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_creates_payout_for_paid_topup_when_missing(): void
    {
        $partner = User::factory()->create([
            'is_partner' => true,
            'referral_code' => 'BFTEST1',
        ]);
        $payer = User::factory()->create(['partner_id' => $partner->id]);

        $orderId = 'bal-backfill-'.uniqid();
        ShortlinkTransaction::create([
            'order_id' => $orderId,
            'amount' => 10,
            'currency' => 'USD',
            'status' => 'paid',
            'identifier' => 'user:'.$payer->id,
            'count' => 0,
            'url' => null,
            'provider_ref' => 'paid-gateway-uuid',
            'payment_kind' => ShortlinkTransaction::KIND_BALANCE_TOPUP,
        ]);

        $this->assertSame(0, PartnerCommissionPayout::where('partner_user_id', $partner->id)->count());

        $this->artisan('partner:backfill-commissions', ['--partner' => (string) $partner->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('partner_commission_payouts', [
            'source_user_id' => $payer->id,
            'partner_user_id' => $partner->id,
            'source_type' => 'heleket_topup',
            'source_id' => $orderId,
            'status' => PartnerCommissionPayout::STATUS_CREDITED_BALANCE,
        ]);

        $partner->refresh();
        $this->assertGreaterThan(0, (float) $partner->balance);
        $this->assertDatabaseHas('shortlink_transactions', [
            'payment_kind' => ShortlinkTransaction::KIND_PARTNER_REFERRAL,
            'identifier' => 'user:'.$partner->id,
        ]);
    }

    public function test_backfill_dry_run_does_not_insert(): void
    {
        $partner = User::factory()->create([
            'is_partner' => true,
            'referral_code' => 'BFTEST2',
        ]);
        $payer = User::factory()->create(['partner_id' => $partner->id]);

        ShortlinkTransaction::create([
            'order_id' => 'bal-dry-'.uniqid(),
            'amount' => 5,
            'currency' => 'USD',
            'status' => 'paid',
            'identifier' => 'user:'.$payer->id,
            'count' => 0,
            'url' => null,
            'provider_ref' => 'uuid',
            'payment_kind' => ShortlinkTransaction::KIND_BALANCE_TOPUP,
        ]);

        $this->artisan('partner:backfill-commissions', [
            '--partner' => (string) $partner->id,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertSame(0, PartnerCommissionPayout::count());
    }
}

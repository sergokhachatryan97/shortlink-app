<?php

namespace App\Services;

use App\Models\PartnerCommissionPayout;
use App\Models\PartnerPayoutSetting;
use App\Models\ShortlinkSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PartnerCommissionService
{
    public const DEFAULT_COMMISSION_PERCENT = 10.00;

    /**
     * Get the effective commission percent for a partner (used for display and calculation).
     * Same resolution order as recordCommission.
     */
    public static function getEffectiveCommissionPercent(User $partner): float
    {
        $payoutProvider = strtolower(trim(
            $partner->payout_provider
                ?? ShortlinkSetting::get('partner_default_payout_provider')
                ?? config('partner.default_payout_provider', 'heleket')
        ));
        $settings = PartnerPayoutSetting::where('user_id', $partner->id)
            ->where('provider', $payoutProvider)
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->first();

        return (float) ($partner->commission_percent
            ?? $settings?->percent
            ?? ShortlinkSetting::get('partner_default_commission_percent')
            ?? self::DEFAULT_COMMISSION_PERCENT);
    }

    /**
     * Record a partner commission from a referred user's payment.
     *
     * Source provider (heleket/coinrush) = where the payment came from.
     * Payout provider = admin-controlled; which system pays the partner.
     * These are independent: e.g. CoinRush payment can be paid out via Heleket.
     */
    public function recordCommission(
        User $sourceUser,
        float $sourceAmount,
        string $sourceType,
        ?string $sourceId = null,
        ?string $sourceProvider = null,
    ): ?PartnerCommissionPayout {
        if ($sourceAmount <= 0) {
            return null;
        }

        $partner = $sourceUser->partner;
        if (!$partner || !$partner->is_partner) {
            return null;
        }

        if ($partner->id === $sourceUser->id) {
            Log::warning('PartnerCommissionService: self-referral prevented', ['user_id' => $sourceUser->id]);
            return null;
        }

        $exists = PartnerCommissionPayout::where('source_user_id', $sourceUser->id)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists();
        if ($exists) {
            return null;
        }

        $payoutProvider = $this->resolvePayoutProvider($partner);
        $allowed = config('partner.allowed_payout_providers', ['heleket', 'coinrush']);
        if (!in_array($payoutProvider, $allowed, true)) {
            Log::warning('PartnerCommissionService: invalid payout provider', [
                'partner_id' => $partner->id,
                'payout_provider' => $payoutProvider,
            ]);
            return null;
        }

        $settings = PartnerPayoutSetting::where('user_id', $partner->id)
            ->where('provider', $payoutProvider)
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->first();

        if (!$settings || empty(trim($settings->wallet_address ?? ''))) {
            Log::info('PartnerCommissionService: no active payout settings for partner', [
                'partner_id' => $partner->id,
                'payout_provider' => $payoutProvider,
            ]);
            return null;
        }

        $percent = (float) ($partner->commission_percent
            ?? $settings->percent
            ?? ShortlinkSetting::get('partner_default_commission_percent')
            ?? self::DEFAULT_COMMISSION_PERCENT);
        $commissionAmount = round($sourceAmount * ($percent / 100), 2);

        if ($commissionAmount < 0.01) {
            return null;
        }

        return DB::transaction(function () use (
            $sourceUser,
            $partner,
            $settings,
            $sourceAmount,
            $percent,
            $commissionAmount,
            $sourceType,
            $sourceId,
            $sourceProvider,
        ) {
            $payout = PartnerCommissionPayout::create([
                'source_user_id' => $sourceUser->id,
                'partner_user_id' => $partner->id,
                'provider' => $settings->provider,
                'source_provider' => $sourceProvider ? strtolower($sourceProvider) : null,
                'source_amount' => $sourceAmount,
                'commission_percent' => $percent,
                'commission_amount' => $commissionAmount,
                'currency' => $settings->currency ?? 'USDT',
                'network' => $settings->network,
                'wallet_address' => $settings->wallet_address,
                'status' => PartnerCommissionPayout::STATUS_PENDING,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]);

            Log::info('PartnerCommissionService: commission recorded', [
                'payout_id' => $payout->id,
                'partner_id' => $partner->id,
                'source_provider' => $sourceProvider,
                'payout_provider' => $settings->provider,
                'source_amount' => $sourceAmount,
                'commission_amount' => $commissionAmount,
            ]);

            return $payout;
        });
    }

    /**
     * Resolve which payout provider to use for this partner.
     * Priority: partner's admin-set payout_provider, then global admin setting, then config.
     */
    private function resolvePayoutProvider(User $partner): string
    {
        $provider = $partner->payout_provider
            ?? ShortlinkSetting::get('partner_default_payout_provider')
            ?? config('partner.default_payout_provider', 'heleket');

        return strtolower(trim($provider));
    }
}

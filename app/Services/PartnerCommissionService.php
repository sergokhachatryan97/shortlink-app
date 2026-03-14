<?php

namespace App\Services;

use App\Jobs\SendPartnerPayoutJob;
use App\Models\PartnerCommissionPayout;
use App\Models\PartnerPayoutSetting;
use App\Models\ShortlinkSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PartnerCommissionService
{
    public const DEFAULT_COMMISSION_PERCENT = 10.00;

    public function __construct(
        protected PayoutRouteResolver $routeResolver
    ) {}

    /**
     * Get the effective commission percent for a partner (used for display and calculation).
     * Same resolution order as recordCommission.
     */
    public function getEffectiveCommissionPercent(User $partner): float
    {
        $payoutProvider = $this->resolvePayoutProvider($partner);
        $defaultRoute = $this->routeResolver->getDefaultRoute($payoutProvider);
        $settings = PartnerPayoutSetting::where('user_id', $partner->id)
            ->where('provider', $payoutProvider)
            ->where('currency', $defaultRoute['currency'])
            ->where('network', $defaultRoute['network'])
            ->where('is_active', true)
            ->whereNotNull('currency')
            ->whereNotNull('network')
            ->where('currency', '!=', '')
            ->where('network', '!=', '')
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
        $allowed = config('partner.allowed_payout_providers', ['heleket']);
        if (!in_array($payoutProvider, $allowed, true)) {
            $payoutProvider = 'heleket';
        }

        $defaultRoute = $this->routeResolver->getDefaultRoute($payoutProvider);
        $currency = $defaultRoute['currency'];
        $network = $defaultRoute['network'];

        $settings = PartnerPayoutSetting::where('user_id', $partner->id)
            ->where('provider', $payoutProvider)
            ->where('currency', $currency)
            ->where('network', $network)
            ->where('is_active', true)
            ->whereNotNull('currency')
            ->whereNotNull('network')
            ->where('currency', '!=', '')
            ->where('network', '!=', '')
            ->orderByDesc('updated_at')
            ->first();

        if (!$settings || empty(trim($settings->wallet_address ?? ''))) {
            Log::info('PartnerCommissionService: no valid payout settings for partner route', [
                'partner_id' => $partner->id,
                'payout_provider' => $payoutProvider,
                'currency' => $currency,
                'network' => $network,
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

        $idsToPayout = [];

        $payout = DB::transaction(function () use (
            $sourceUser,
            $partner,
            $settings,
            $sourceAmount,
            $percent,
            $commissionAmount,
            $sourceType,
            $sourceId,
            $sourceProvider,
            &$idsToPayout,
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

            $idsToPayout = $this->checkAndTriggerPayoutForBatch($payout);

            return $payout;
        });

        if (!empty($idsToPayout)) {
            SendPartnerPayoutJob::dispatch($idsToPayout);
        }

        return $payout;
    }

    /**
     * Check if the partner's pending batch meets the minimum and trigger payout.
     * Uses row locking to prevent duplicate payouts. Returns IDs to dispatch, or empty array.
     *
     * @return array<int>
     */
    private function checkAndTriggerPayoutForBatch(PartnerCommissionPayout $payout): array
    {
        $minPayout = (float) (ShortlinkSetting::get('partner_min_payout_amount') ?? config('partner.default_min_payout_amount', 100));
        $enabledProviders = config('partner.payout_providers_enabled', ['heleket']);
        $provider = strtolower($payout->provider ?? '');

        if (!in_array($provider, $enabledProviders, true)) {
            return [];
        }

        $batch = PartnerCommissionPayout::where('partner_user_id', $payout->partner_user_id)
            ->where('provider', $payout->provider)
            ->where('currency', $payout->currency ?? 'USDT')
            ->where('network', $payout->network ?? '')
            ->where('wallet_address', $payout->wallet_address ?? '')
            ->where('status', PartnerCommissionPayout::STATUS_PENDING)
            ->lockForUpdate()
            ->orderBy('id')
            ->get();

        $total = $batch->sum(fn ($p) => (float) $p->commission_amount);

        if ($total < $minPayout) {
            return [];
        }

        $ids = $batch->pluck('id')->all();

        PartnerCommissionPayout::whereIn('id', $ids)->update([
            'status' => PartnerCommissionPayout::STATUS_PROCESSING,
        ]);

        Log::info('PartnerCommissionService: payout triggered', [
            'partner_id' => $payout->partner_user_id,
            'batch_ids' => $ids,
            'total' => $total,
            'min' => $minPayout,
        ]);

        return $ids;
    }

    /**
     * Resolve payout provider. Platform supports only Heleket (TRON).
     */
    private function resolvePayoutProvider(User $partner): string
    {
        return 'heleket';
    }
}

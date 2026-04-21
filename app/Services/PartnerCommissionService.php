<?php

namespace App\Services;

use App\Models\PartnerCommissionPayout;
use App\Models\PartnerPayoutSetting;
use App\Models\ShortlinkSetting;
use App\Models\ShortlinkTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PartnerCommissionService
{
    public const DEFAULT_COMMISSION_PERCENT = 10.00;

    public function __construct(
        protected PayoutRouteResolver $routeResolver,
        protected BalanceService $balanceService,
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
     * When {@see config('partner.referral_credits_to_balance')} is true (default), the partner's
     * Trastly balance is credited immediately and no withdrawal row stays pending.
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
        if (! $partner || ! $partner->is_partner) {
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
        if (! in_array($payoutProvider, $allowed, true)) {
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

        $walletAddress = null;
        if ($settings && $settings->wallet_address !== null && $settings->wallet_address !== '') {
            $w = trim((string) $settings->wallet_address);
            $walletAddress = $w !== '' ? $w : null;
        }

        if ($walletAddress === null && ! (bool) config('partner.referral_credits_to_balance', true)) {
            Log::info('PartnerCommissionService: recording commission without saved payout wallet (partner can add USDT TRC20 in dashboard)', [
                'partner_id' => $partner->id,
                'payout_provider' => $payoutProvider,
                'currency' => $currency,
                'network' => $network,
            ]);
        }

        $percent = (float) ($partner->commission_percent
            ?? $settings?->percent
            ?? ShortlinkSetting::get('partner_default_commission_percent')
            ?? self::DEFAULT_COMMISSION_PERCENT);
        $commissionAmount = round($sourceAmount * ($percent / 100), 2);

        if ($commissionAmount < 0.01) {
            return null;
        }

        $recordProvider = ($settings && $settings->provider) ? $settings->provider : $payoutProvider;

        $creditToBalance = (bool) config('partner.referral_credits_to_balance', true);

        $payout = DB::transaction(function () use (
            $sourceUser,
            $partner,
            $settings,
            $recordProvider,
            $walletAddress,
            $currency,
            $network,
            $sourceAmount,
            $percent,
            $commissionAmount,
            $sourceType,
            $sourceId,
            $sourceProvider,
            $creditToBalance,
        ) {
            $status = $creditToBalance
                ? PartnerCommissionPayout::STATUS_CREDITED_BALANCE
                : PartnerCommissionPayout::STATUS_PENDING;

            $payout = PartnerCommissionPayout::create([
                'source_user_id' => $sourceUser->id,
                'partner_user_id' => $partner->id,
                'provider' => $recordProvider,
                'source_provider' => $sourceProvider ? strtolower($sourceProvider) : null,
                'source_amount' => $sourceAmount,
                'commission_percent' => $percent,
                'commission_amount' => $commissionAmount,
                'currency' => $settings?->currency ?? $currency,
                'network' => $settings?->network ?? $network,
                'wallet_address' => $walletAddress,
                'status' => $status,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]);

            Log::info('PartnerCommissionService: commission recorded', [
                'payout_id' => $payout->id,
                'partner_id' => $partner->id,
                'source_provider' => $sourceProvider,
                'payout_provider' => $recordProvider,
                'source_amount' => $sourceAmount,
                'commission_amount' => $commissionAmount,
                'credited_to_balance' => $creditToBalance,
            ]);

            if ($creditToBalance) {
                $this->balanceService->incrementBalance(User::class, (int) $partner->id, $commissionAmount);

                ShortlinkTransaction::create([
                    'order_id' => 'refcom-'.$payout->id,
                    'amount' => $commissionAmount,
                    'currency' => 'USD',
                    'status' => 'paid',
                    'identifier' => 'user:'.$partner->id,
                    'count' => 0,
                    'url' => null,
                    'provider_ref' => 'referral_commission',
                    'payment_kind' => ShortlinkTransaction::KIND_PARTNER_REFERRAL,
                ]);
            }

            return $payout;
        });

        return $payout;
    }

    /**
     * Sum of commission_amount for the partner that is still available for withdrawal (status = pending).
     * Used for partner dashboard and manual withdrawal eligibility.
     */
    public function getAvailableWithdrawAmount(User $partner): float
    {
        if ((bool) config('partner.referral_credits_to_balance', true)) {
            return 0.0;
        }

        $sum = PartnerCommissionPayout::where('partner_user_id', $partner->id)
            ->whereIn('status', [PartnerCommissionPayout::STATUS_PENDING, PartnerCommissionPayout::STATUS_REJECTED])
            ->sum('commission_amount');

        return round((float) $sum, 2);
    }

    /**
     * Total referral commission already credited to the partner's balance (all time).
     */
    public function getTotalCreditedToBalance(User $partner): float
    {
        $sum = PartnerCommissionPayout::where('partner_user_id', $partner->id)
            ->where('status', PartnerCommissionPayout::STATUS_CREDITED_BALANCE)
            ->sum('commission_amount');

        return round((float) $sum, 2);
    }

    /**
     * Minimum amount (USD) required to request a withdrawal. From admin settings or config default.
     */
    public function getMinWithdrawAmount(): float
    {
        return (float) (ShortlinkSetting::get('partner_min_payout_amount') ?? config('partner.default_min_payout_amount', 100));
    }

    /**
     * Resolve payout provider. Platform supports only Heleket (TRON).
     */
    private function resolvePayoutProvider(User $partner): string
    {
        return 'heleket';
    }
}

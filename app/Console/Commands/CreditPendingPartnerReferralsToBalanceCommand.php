<?php

namespace App\Console\Commands;

use App\Models\PartnerCommissionPayout;
use App\Models\ShortlinkTransaction;
use App\Models\User;
use App\Services\BalanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time (or safe repeat) migration: legacy partner_commission_payouts rows that were
 * left in "pending" before referral credits-to-balance shipped are settled into balance.
 */
class CreditPendingPartnerReferralsToBalanceCommand extends Command
{
    protected $signature = 'partner:credit-pending-referrals-balance {--dry-run : Show counts only}';

    protected $description = 'Credit legacy pending partner referral commissions to each partner\'s balance (and mark credited_balance)';

    public function handle(BalanceService $balanceService): int
    {
        if (! (bool) config('partner.referral_credits_to_balance', true)) {
            $this->error('partner.referral_credits_to_balance is disabled; aborting.');

            return self::FAILURE;
        }

        $query = PartnerCommissionPayout::query()
            ->where('status', PartnerCommissionPayout::STATUS_PENDING)
            ->orderBy('id');

        $count = PartnerCommissionPayout::query()
            ->where('status', PartnerCommissionPayout::STATUS_PENDING)
            ->count();
        if ($count === 0) {
            $this->info('No pending partner referral rows to credit.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("Dry run: {$count} pending row(s) would be credited to partner balances.");

            return self::SUCCESS;
        }

        $credited = 0;
        $skipped = 0;

        foreach ($query->cursor() as $payout) {
            $ok = DB::transaction(function () use ($payout, $balanceService) {
                $row = PartnerCommissionPayout::whereKey($payout->id)->lockForUpdate()->first();
                if (! $row || $row->status !== PartnerCommissionPayout::STATUS_PENDING) {
                    return false;
                }

                $amount = (float) $row->commission_amount;
                if ($amount < 0.01) {
                    $row->update(['status' => PartnerCommissionPayout::STATUS_CREDITED_BALANCE]);

                    return true;
                }

                $balanceService->incrementBalance(User::class, (int) $row->partner_user_id, $amount);
                $row->update(['status' => PartnerCommissionPayout::STATUS_CREDITED_BALANCE]);

                ShortlinkTransaction::create([
                    'order_id' => 'refcom-'.$row->id,
                    'amount' => $amount,
                    'currency' => 'USD',
                    'status' => 'paid',
                    'identifier' => 'user:'.$row->partner_user_id,
                    'count' => 0,
                    'url' => null,
                    'provider_ref' => 'referral_commission',
                    'payment_kind' => ShortlinkTransaction::KIND_PARTNER_REFERRAL,
                ]);

                return true;
            });

            if ($ok) {
                $credited++;
            } else {
                $skipped++;
            }
        }

        $this->info("Credited {$credited} row(s); skipped {$skipped}.");

        return self::SUCCESS;
    }
}

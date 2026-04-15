<?php

namespace App\Console\Commands;

use App\Models\ShortlinkTransaction;
use App\Models\User;
use App\Services\PartnerCommissionService;
use Illuminate\Console\Command;

class BackfillPartnerCommissionsCommand extends Command
{
    protected $signature = 'partner:backfill-commissions
                            {--partner= : Only process payers whose users.partner_id matches this partner user id}
                            {--dry-run : List what would be processed without inserting}';

    protected $description = 'Create missing partner_commission_payouts from paid gateway shortlink_transactions (idempotent; uses PartnerCommissionService dedupe)';

    public function handle(PartnerCommissionService $commissionService): int
    {
        $partnerFilter = $this->option('partner');
        $partnerFilter = $partnerFilter !== null && $partnerFilter !== '' ? (int) $partnerFilter : null;
        $dryRun = (bool) $this->option('dry-run');

        $query = ShortlinkTransaction::query()
            ->where('status', 'paid')
            ->where('amount', '>', 0)
            ->where('identifier', 'like', 'user:%')
            ->where(function ($q) {
                $q->where(function ($b) {
                    $b->where('payment_kind', ShortlinkTransaction::KIND_BALANCE_TOPUP)
                        ->orWhere('order_id', 'like', 'bal-%');
                })->orWhere(function ($s) {
                    $s->where('payment_kind', ShortlinkTransaction::KIND_SHORTLINK_PAYMENT)
                        ->orWhere(function ($s2) {
                            $s2->where('order_id', 'like', 'sl-%')
                                ->where('count', '>', 0)
                                ->whereNotNull('url')
                                ->where('url', '!=', '');
                        });
                });
            })
            ->orderBy('id');

        $would = 0;
        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($query->cursor() as $tx) {
            $kind = $this->classifyPaidRow($tx);
            if ($kind === null) {
                continue;
            }

            if (! preg_match('/^user:(\d+)$/', (string) $tx->identifier, $m)) {
                continue;
            }
            $payerId = (int) $m[1];
            $payer = User::query()->with('partner')->find($payerId);
            if (! $payer || ! $payer->partner_id) {
                $skipped++;

                continue;
            }

            if ($partnerFilter !== null && (int) $payer->partner_id !== $partnerFilter) {
                continue;
            }

            $partner = $payer->partner;
            if (! $partner || ! $partner->is_partner) {
                $this->warn("Skip payer {$payerId}: partner {$payer->partner_id} missing or not is_partner");
                $skipped++;

                continue;
            }

            [$sourceType, $sourceProvider] = $kind === 'balance_topup'
                ? $this->inferTopupMeta($tx)
                : $this->inferShortlinkMeta($tx);

            if ($dryRun) {
                $would++;
                if ($would <= 20) {
                    $this->line("Would backfill: tx {$tx->id} order {$tx->order_id} payer {$payerId} → partner {$partner->id} {$sourceType} \${$tx->amount}");
                }

                continue;
            }

            try {
                $payout = $commissionService->recordCommission(
                    $payer,
                    (float) $tx->amount,
                    $sourceType,
                    $tx->order_id,
                    $sourceProvider,
                );
                if ($payout) {
                    $created++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $errors++;
                $this->error("Tx {$tx->id}: {$e->getMessage()}");
            }
        }

        if ($dryRun) {
            $this->info("Dry run: {$would} transaction(s) would be attempted for partner filter ".($partnerFilter ?? 'all').'.');

            return self::SUCCESS;
        }

        $this->info("Created {$created} payout row(s); skipped/duplicate/no-op {$skipped}; errors {$errors}.");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function classifyPaidRow(ShortlinkTransaction $tx): ?string
    {
        if ($tx->payment_kind === ShortlinkTransaction::KIND_BALANCE_TOPUP
            || str_starts_with((string) $tx->order_id, 'bal-')) {
            return 'balance_topup';
        }

        if ($tx->payment_kind === ShortlinkTransaction::KIND_SHORTLINK_PAYMENT) {
            return 'shortlink';
        }

        if (str_starts_with((string) $tx->order_id, 'sl-')
            && ($tx->count ?? 0) > 0
            && filled($tx->url)) {
            return 'shortlink';
        }

        return null;
    }

    /**
     * @return array{0: string, 1: string} [sourceType, sourceProvider]
     */
    private function inferTopupMeta(ShortlinkTransaction $tx): array
    {
        $pr = strtolower((string) ($tx->provider_ref ?? ''));
        if (str_contains($pr, 'tron') || str_contains($pr, 'coinrush')) {
            return ['coinrush_topup', 'coinrush'];
        }

        return ['heleket_topup', 'heleket'];
    }

    /**
     * @return array{0: string, 1: string} [sourceType, sourceProvider]
     */
    private function inferShortlinkMeta(ShortlinkTransaction $tx): array
    {
        $pr = strtolower((string) ($tx->provider_ref ?? ''));
        if (str_starts_with($pr, 'tron:') || $pr === 'tron' || str_contains($pr, 'coinrush')) {
            return ['coinrush_shortlink', 'coinrush'];
        }

        return ['heleket_shortlink', 'heleket'];
    }
}

<?php

namespace App\Console\Commands;

use App\Jobs\SendPartnerPayoutJob;
use App\Models\PartnerCommissionPayout;
use App\Models\ShortlinkSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPartnerPayoutsCommand extends Command
{
    protected $signature = 'partner:process-payouts';

    protected $description = 'Process pending partner commissions (manual run): aggregate by partner/provider/currency/network/wallet and send payouts when batch meets minimum';

    public function handle(): int
    {
        $enabledProviders = config('partner.payout_providers_enabled', ['heleket']);
        $minPayoutAmount = (float) (ShortlinkSetting::get('partner_min_payout_amount') ?? config('partner.default_min_payout_amount', 100));

        $batches = DB::transaction(function () use ($enabledProviders, $minPayoutAmount) {
            return PartnerCommissionPayout::where('status', PartnerCommissionPayout::STATUS_PENDING)
                ->whereNotNull('currency')
                ->whereNotNull('network')
                ->where('currency', '!=', '')
                ->where('network', '!=', '')
                ->orderBy('id')
                ->get()
                ->groupBy(fn ($p) => $this->batchKey($p))
                ->map(fn ($group) => [
                    'ids' => $group->pluck('id')->all(),
                    'total' => $group->sum(fn ($p) => (float) $p->commission_amount),
                    'provider' => $group->first()->provider ?? null,
                ])
                ->filter(fn ($b) => $b['total'] > 0)
                ->filter(function ($b) use ($minPayoutAmount) {
                    if ($b['total'] < $minPayoutAmount) {
                        Log::info('ProcessPartnerPayoutsCommand: skipping batch - below minimum', [
                            'total' => $b['total'],
                            'min' => $minPayoutAmount,
                            'count' => count($b['ids']),
                        ]);
                        return false;
                    }
                    return true;
                })
                ->filter(function ($b) use ($enabledProviders) {
                    $provider = strtolower($b['provider'] ?? '');
                    if (!in_array($provider, $enabledProviders, true)) {
                        Log::info('ProcessPartnerPayoutsCommand: skipping batch - provider not enabled', [
                            'provider' => $provider,
                            'enabled' => $enabledProviders,
                            'count' => count($b['ids']),
                        ]);
                        return false;
                    }
                    return true;
                })
                ->values()
                ->all();
        });

        $dispatched = 0;
        foreach ($batches as $batch) {
            SendPartnerPayoutJob::dispatch($batch['ids']);
            $dispatched++;
        }

        if ($dispatched > 0) {
            $this->info("Dispatched {$dispatched} batch payout job(s).");
        }

        return Command::SUCCESS;
    }

    private function batchKey(PartnerCommissionPayout $p): string
    {
        return implode('|', [
            $p->partner_user_id,
            $p->provider,
            $p->currency ?? 'USDT',
            $p->network ?? '',
            $p->wallet_address ?? '',
        ]);
    }
}

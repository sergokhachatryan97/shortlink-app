<?php

namespace App\Console\Commands;

use App\Jobs\SendPartnerPayoutJob;
use App\Models\PartnerCommissionPayout;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPartnerPayoutsCommand extends Command
{
    protected $signature = 'partner:process-payouts';

    protected $description = 'Process pending partner commissions: aggregate by partner/payout_provider/currency/network and send daily payouts';

    public function handle(): int
    {
        $enabledProviders = config('partner.payout_providers_enabled', ['heleket']);

        $batches = DB::transaction(function () use ($enabledProviders) {
            return PartnerCommissionPayout::where('status', PartnerCommissionPayout::STATUS_PENDING)
                ->orderBy('id')
                ->get()
                ->groupBy(fn ($p) => $this->batchKey($p))
                ->map(fn ($group) => [
                    'ids' => $group->pluck('id')->all(),
                    'total' => $group->sum(fn ($p) => (float) $p->commission_amount),
                    'provider' => $group->first()->provider ?? null,
                ])
                ->filter(fn ($b) => $b['total'] > 0)
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

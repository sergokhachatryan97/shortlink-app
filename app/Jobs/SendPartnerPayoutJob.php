<?php

namespace App\Jobs;

use App\Models\PartnerCommissionPayout;
use App\Services\PartnerPayoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendPartnerPayoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * @param array<int> $commissionIds IDs of commission records in this batch
     */
    public function __construct(
        public array $commissionIds
    ) {}

    public function handle(PartnerPayoutService $payoutService): void
    {
        if (empty($this->commissionIds)) {
            return;
        }

        $commissions = DB::transaction(function () {
            $rows = PartnerCommissionPayout::whereIn('id', $this->commissionIds)
                ->lockForUpdate()
                ->orderBy('id')
                ->get();

            if ($rows->isEmpty()) {
                return [];
            }

            $allPending = $rows->every(fn ($p) => $p->isPending());
            if (!$allPending) {
                $settled = $rows->filter(fn ($p) => $p->isSettled());
                if ($settled->isNotEmpty()) {
                    Log::info('SendPartnerPayoutJob: batch already settled', [
                        'ids' => $this->commissionIds,
                        'settled' => $settled->pluck('id')->all(),
                    ]);
                    return [];
                }
                $allProcessing = $rows->every(fn ($p) => $p->isProcessing());
                $stale = $rows->filter(fn ($p) => $p->updated_at && $p->updated_at->lt(now()->subHour()));
                if ($allProcessing && $stale->count() === $rows->count()) {
                    PartnerCommissionPayout::whereIn('id', $this->commissionIds)->update([
                        'status' => PartnerCommissionPayout::STATUS_PENDING,
                    ]);
                    $rows = PartnerCommissionPayout::whereIn('id', $this->commissionIds)
                        ->orderBy('id')
                        ->get();
                } else {
                    return [];
                }
            }

            PartnerCommissionPayout::whereIn('id', $this->commissionIds)->update([
                'status' => PartnerCommissionPayout::STATUS_PROCESSING,
            ]);

            return $rows->all();
        });

        if (empty($commissions)) {
            return;
        }

        $payoutService->sendBatchPayout($commissions);
    }

    public function uniqueId(): string
    {
        return 'partner-payout-batch-' . implode('-', $this->commissionIds);
    }
}

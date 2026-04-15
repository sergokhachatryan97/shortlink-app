<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use App\Services\PartnerCommissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait DefersPartnerCommissionAfterPayment
{
    /**
     * Run partner commission after the payment DB transaction commits so a commission
     * exception cannot roll back balance credit / link generation.
     */
    private function deferPartnerCommission(int $userId, float $amount, string $sourceType, string $sourceId, string $sourceProvider): void
    {
        DB::afterCommit(function () use ($userId, $amount, $sourceType, $sourceId, $sourceProvider) {
            try {
                $user = User::find($userId);
                if (! $user) {
                    Log::warning('Partner commission skipped: user not found', ['user_id' => $userId]);

                    return;
                }
                app(PartnerCommissionService::class)->recordCommission($user, $amount, $sourceType, $sourceId, $sourceProvider);
            } catch (\Throwable $e) {
                Log::error('Partner commission failed after payment commit', [
                    'user_id' => $userId,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'source_provider' => $sourceProvider,
                    'exception' => $e->getMessage(),
                ]);
            }
        });
    }
}

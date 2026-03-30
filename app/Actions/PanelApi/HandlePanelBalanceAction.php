<?php

namespace App\Actions\PanelApi;

use App\Models\ExternalClient;
use App\Models\User;
use App\Services\ShortlinkEntitlementService;
use App\Support\MoneyDisplay;

class HandlePanelBalanceAction
{
    public function __construct(
        protected ShortlinkEntitlementService $entitlement
    ) {}

    public function execute(ExternalClient $client): array
    {
        $effectiveBalance = (float) $client->balance;

        /** @var User|null $user */
        $user = null;
        if ($client->user_id) {
            $user = User::query()->find($client->user_id);
            if ($user) {
                // Keep external client ledger synced with user wallet for linked clients.
                $userBalance = (float) $user->balance;
                $effectiveBalance = min($effectiveBalance, $userBalance);
                if ((float) $client->balance !== $userBalance) {
                    $client->update(['balance' => $userBalance]);
                    $effectiveBalance = $userBalance;
                }
            }
        }

        $payload = [
            'balance' => MoneyDisplay::plainDecimal($effectiveBalance),
            'currency' => $client->currency,
        ];

        if ($user) {
            $payload = array_merge(
                $payload,
                $this->entitlement->linkedAccountPanelSummary($user, (string) (request()->ip() ?? '0.0.0.0'))
            );
        }

        return $payload;
    }
}

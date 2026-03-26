<?php

namespace App\Actions\PanelApi;

use App\Models\ExternalClient;
use App\Models\User;

class HandlePanelBalanceAction
{
    public function execute(ExternalClient $client): array
    {
        $effectiveBalance = (float) $client->balance;

        if ($client->user_id) {
            /** @var User|null $user */
            $user = User::query()->find($client->user_id);
            if ($user) {
                // Keep external client ledger synced with user wallet for linked clients.
                $userBalance = (float) $user->balance;
                $effectiveBalance = min($effectiveBalance, $userBalance);
                if (round((float) $client->balance, 2) !== round($userBalance, 2)) {
                    $client->update(['balance' => $userBalance]);
                    $effectiveBalance = $userBalance;
                }
            }
        }

        return [
            'balance' => number_format($effectiveBalance, 2, '.', ''),
            'currency' => $client->currency,
        ];
    }
}

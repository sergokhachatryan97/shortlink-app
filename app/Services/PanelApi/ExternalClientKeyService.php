<?php

namespace App\Services\PanelApi;

use App\Models\ExternalClient;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class ExternalClientKeyService
{
    public function generateRawKey(): string
    {
        return 'trst_' . Str::random(48);
    }

    public function findForUser(User $user): ?ExternalClient
    {
        return ExternalClient::query()->where('user_id', $user->id)->first();
    }

    public function regenerateForUser(User $user): array
    {
        $client = $this->findForUser($user);
        if (!$client) {
            $client = ExternalClient::query()->create([
                'user_id' => $user->id,
                'name' => $user->name . ' API Client',
                'api_key_hash' => hash('sha256', $this->generateRawKey()),
                'api_key_encrypted' => null,
                'is_active' => true,
                'balance' => $user->balance ?? 0,
                'currency' => 'USD',
            ]);
        }
        $rawKey = $this->generateRawKey();

        $client->update([
            'api_key_hash' => hash('sha256', $rawKey),
            'api_key_encrypted' => Crypt::encryptString($rawKey),
            'is_active' => true,
        ]);

        return [$client, $rawKey];
    }

    public function revealKey(ExternalClient $client): ?string
    {
        if (empty($client->api_key_encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($client->api_key_encrypted);
        } catch (\Throwable) {
            return null;
        }
    }
}

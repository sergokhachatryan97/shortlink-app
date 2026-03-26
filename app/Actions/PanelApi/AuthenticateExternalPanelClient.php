<?php

namespace App\Actions\PanelApi;

use App\Models\ExternalClient;
use Illuminate\Http\Request;

class AuthenticateExternalPanelClient
{
    public function execute(Request $request): ?ExternalClient
    {
        $apiKey = trim((string) $request->input('key', ''));
        if ($apiKey === '') {
            return null;
        }

        $hash = hash('sha256', $apiKey);

        return ExternalClient::query()
            ->where('api_key_hash', $hash)
            ->where('is_active', true)
            ->first();
    }
}

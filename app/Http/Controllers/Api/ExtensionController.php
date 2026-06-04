<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExternalClient;
use App\Services\ShortenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExtensionController extends Controller
{
    public function shorten(Request $request, ShortenService $shortenService): JsonResponse
    {
        $token = $request->bearerToken();
        if (! $token) {
            return response()->json(['error' => 'API key required'], 401);
        }

        $hash = hash('sha256', $token);
        $client = ExternalClient::where('api_key_hash', $hash)->where('is_active', true)->first();

        if (! $client) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        $url = $request->input('url');
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'Valid URL required'], 400);
        }

        $links = $shortenService->shorten($url, 1);
        $shortUrl = $links[0] ?? null;

        if (! $shortUrl) {
            return response()->json(['error' => 'Failed to shorten URL'], 500);
        }

        return response()->json(['short_url' => $shortUrl]);
    }
}

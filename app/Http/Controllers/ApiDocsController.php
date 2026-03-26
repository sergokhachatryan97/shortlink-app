<?php

namespace App\Http\Controllers;

use App\Services\PanelApi\ExternalClientKeyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiDocsController extends Controller
{
    public function index(Request $request, ExternalClientKeyService $keyService): View
    {
        $user = $request->user();
        $client = $keyService->findForUser($user);
        $currentApiKey = $client ? $keyService->revealKey($client) : null;

        return view('api.docs', [
            'client' => $client,
            'endpointUrl' => rtrim(config('app.url'), '/') . '/api/v2',
            'newApiKey' => session('new_api_key'),
            'currentApiKey' => $currentApiKey,
        ]);
    }

    public function regenerate(Request $request, ExternalClientKeyService $keyService): RedirectResponse
    {
        $user = $request->user();
        [, $rawKey] = $keyService->regenerateForUser($user);

        return redirect()
            ->route('api.docs')
            ->with('success', 'API key regenerated successfully.')
            ->with('new_api_key', $rawKey);
    }
}

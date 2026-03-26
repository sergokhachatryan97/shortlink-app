<?php

namespace App\Http\Controllers\PanelApi;

use App\Actions\PanelApi\HandlePanelAddAction;
use App\Actions\PanelApi\HandlePanelBalanceAction;
use App\Actions\PanelApi\HandlePanelCancelAction;
use App\Actions\PanelApi\HandlePanelMultipleStatusAction;
use App\Actions\PanelApi\HandlePanelServicesAction;
use App\Actions\PanelApi\HandlePanelStatusAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PanelApiController extends Controller
{
    public function __invoke(
        Request $request,
        HandlePanelServicesAction $servicesAction,
        HandlePanelBalanceAction $balanceAction,
        HandlePanelAddAction $addAction,
        HandlePanelStatusAction $statusAction,
        HandlePanelMultipleStatusAction $multipleStatusAction,
        HandlePanelCancelAction $cancelAction
    ): JsonResponse {
        $client = $request->attributes->get('externalClient');

        $action = trim((string) $request->input('action', ''));
        if ($action === '') {
            return response()->json(['error' => 'Action parameter is required'], 422);
        }

        $result = match ($action) {
            'services' => $servicesAction->execute(),
            'balance' => $balanceAction->execute($client),
            'add' => $addAction->execute($client, $request->all()),
            'status' => $statusAction->execute($client, (int) $request->input('order', 0)),
            'multiple_status' => $multipleStatusAction->execute($client, (string) $request->input('orders', '')),
            'cancel' => $cancelAction->execute($client, (int) $request->input('order', 0)),
            default => ['error' => 'Unknown action'],
        };

        return response()->json($result);
    }
}

<?php

namespace App\Actions\PanelApi;

use App\Models\ExternalClient;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class HandlePanelCancelAction
{
    public function execute(ExternalClient $client, int $orderId): array
    {
        if ($orderId <= 0) {
            return ['error' => 'Order parameter is required'];
        }

        $updated = DB::transaction(function () use ($client, $orderId) {
            $order = Order::query()
                ->where('external_client_id', $client->id)
                ->where('id', $orderId)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                return 'not_found';
            }

            if (!$order->canBeCanceled()) {
                return 'not_allowed';
            }

            $order->update([
                'status' => Order::STATUS_CANCELED,
                'remains' => max(0, $order->quantity),
            ]);

            $clientLocked = ExternalClient::query()->lockForUpdate()->find($client->id);
            if ($clientLocked) {
                $clientLocked->increment('balance', (float) $order->charge);
            }

            return 'ok';
        });

        if ($updated === 'not_found') {
            return ['error' => 'Order not found'];
        }
        if ($updated === 'not_allowed') {
            return ['error' => 'Order cannot be canceled'];
        }

        return ['cancel' => true];
    }
}

<?php

namespace App\Actions\PanelApi;

use App\Models\ExternalClient;
use App\Models\Order;
use App\Services\PanelApi\PanelStatusMapper;

class HandlePanelStatusAction
{
    public function __construct(
        protected PanelStatusMapper $statusMapper
    ) {}

    public function execute(ExternalClient $client, int $orderId): array
    {
        if ($orderId <= 0) {
            return ['error' => 'Order parameter is required'];
        }

        $order = Order::query()
            ->where('external_client_id', $client->id)
            ->where('id', $orderId)
            ->first();

        if (!$order) {
            return ['error' => 'Order not found'];
        }

        $response = [
            'charge' => number_format((float) $order->charge, 2, '.', ''),
            'start_count' => $order->start_count ?? 0,
            'status' => $this->statusMapper->map($order->status),
            'remains' => $order->remains ?? max(0, $order->quantity),
            'currency' => $order->currency,
            'original_link' => $order->link,
            'quantity' => (int) $order->quantity,
        ];

        $generatedLinks = $order->metadata['generated_links'] ?? null;
        if (is_array($generatedLinks)) {
            $response['generated_links'] = array_values($generatedLinks);
        }

        return $response;
    }
}

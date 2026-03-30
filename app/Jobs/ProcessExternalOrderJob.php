<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use App\Services\ShortenService;
use App\Services\ShortlinkEntitlementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessExternalOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $orderId) {}

    public function handle(ShortenService $shortenService, ShortlinkEntitlementService $entitlement): void
    {
        $order = Order::find($this->orderId);
        if (!$order || $order->status !== Order::STATUS_VALIDATING) {
            return;
        }

        if (!$order->link) {
            $order->update([
                'status' => Order::STATUS_FAILED,
                'error_code' => 'MISSING_LINK',
                'error_message' => 'Missing original link for generation',
            ]);
            return;
        }

        try {
            $generatedLinks = $shortenService->shorten($order->link, $order->quantity);

            $order->update([
                'status' => Order::STATUS_COMPLETED,
                'start_count' => 0,
                'remains' => 0,
                'metadata' => array_merge($order->metadata ?? [], [
                    'generated_links' => $generatedLinks,
                    'original_link' => $order->link,
                ]),
                'error_code' => null,
                'error_message' => null,
            ]);

            $order->refresh();
            $order->load('externalClient');
            $panelUserId = $order->externalClient?->user_id;
            if ($panelUserId) {
                $panelUser = User::query()->find($panelUserId);
                if ($panelUser) {
                    $clientIp = (string) ($order->metadata['panel_client_ip'] ?? '0.0.0.0');
                    $entitlement->persistPanelOrderSideEffects($order, $panelUser, $clientIp);
                }
            }
        } catch (\Throwable $e) {
            $order->update([
                'status' => Order::STATUS_FAILED,
                'error_code' => 'GENERATION_FAILED',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}

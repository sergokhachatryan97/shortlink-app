<?php

namespace App\Actions\PanelApi;

use App\Jobs\ProcessExternalOrderJob;
use App\Models\ExternalClient;
use App\Models\ExternalService;
use App\Models\Order;
use App\Models\User;
use App\Services\PanelApi\PanelStatusMapper;
use App\Services\ShortenService;
use Illuminate\Support\Facades\DB;

class HandlePanelAddAction
{
    public function __construct(
        protected ShortenService $shortenService,
        protected PanelStatusMapper $statusMapper
    ) {}

    public function execute(ExternalClient $client, array $payload): array
    {
        $serviceId = (int) ($payload['service'] ?? 0);
        $quantity = (int) ($payload['quantity'] ?? 0);
        $link = trim((string) ($payload['link'] ?? ''));

        if ($serviceId <= 0) {
            return ['error' => 'Service parameter is required'];
        }
        if ($quantity <= 0) {
            return ['error' => 'Quantity parameter is required'];
        }

        $service = ExternalService::query()
            ->where('id', $serviceId)
            ->where('is_active', true)
            ->where('is_external_available', true)
            ->first();

        if (!$service) {
            return ['error' => 'Service not found'];
        }

        if ($quantity < (int) $service->min_quantity || $quantity > (int) $service->max_quantity) {
            return ['error' => "Quantity must be between {$service->min_quantity} and {$service->max_quantity}"];
        }

        if ($service->requires_link && $link === '') {
            return ['error' => 'Link parameter is required'];
        }

        if ($link !== '' && filter_var($link, FILTER_VALIDATE_URL) === false) {
            return ['error' => 'Invalid link'];
        }

        $charge = (float) $quantity  * (float) $service->rate;

        $insufficientPayload = null;

        $order = DB::transaction(function () use ($client, $service, $quantity, $link, $charge, &$insufficientPayload) {
            $lockedClient = ExternalClient::query()->lockForUpdate()->find($client->id);
            if (!$lockedClient) {
                return null;
            }

            /** @var User|null $lockedUser */
            $lockedUser = null;
            if ($lockedClient->user_id) {
                $lockedUser = User::query()->lockForUpdate()->find($lockedClient->user_id);
            }

            $clientBalance = (float) $lockedClient->balance;
            $effectiveBalance = $clientBalance;
            if ($lockedUser) {
                $effectiveBalance = min($clientBalance, (float) $lockedUser->balance);
            }

            if ($effectiveBalance < $charge) {
                $current = $effectiveBalance;
                $missing = max(0, round($charge - $current, 2));
                $insufficientPayload = [
                    'error' => 'Not enough balance. Please add balance.',
                    'required' => number_format($charge, 2, '.', ''),
                    'current_balance' => number_format($current, 2, '.', ''),
                    'missing' => number_format($missing, 2, '.', ''),
                    'currency' => $lockedClient->currency,
                    'action' => 'add_balance',
                ];
                return false;
            }

            $lockedClient->decrement('balance', $charge);
            if ($lockedUser) {
                $lockedUser->decrement('balance', $charge);
            }

            return Order::query()->create([
                'external_client_id' => $lockedClient->id,
                'service_id' => $service->id,
                'category_id' => $service->category_id,
                'link' => $link !== '' ? $link : null,
                'quantity' => $quantity,
                'charge' => $charge,
                'currency' => $lockedClient->currency,
                'status' => Order::STATUS_VALIDATING,
            ]);
        });

        if ($order === false) {
            return $insufficientPayload ?? ['error' => 'Not enough balance. Please add balance.'];
        }
        if (!$order) {
            return ['error' => 'Unable to create order'];
        }

        // Prefer synchronous generation for this project so API can return links immediately.
        if ($order->link) {
            try {
                $generatedLinks = $this->shortenService->shorten($order->link, $order->quantity);

                $order->update([
                    'status' => Order::STATUS_COMPLETED,
                    'start_count' => 0,
                    'remains' => 0,
                    'metadata' => array_merge($order->metadata ?? [], [
                        'generated_links' => $generatedLinks,
                        'original_link' => $order->link,
                    ]),
                ]);

                return [
                    'order' => $order->id,
                    'status' => $this->statusMapper->map($order->status),
                    'original_link' => $order->link,
                    'quantity' => (int) $order->quantity,
                    'charge' => number_format((float) $order->charge, 2, '.', ''),
                    'currency' => $order->currency,
                    'generated_links' => $generatedLinks,
                ];
            } catch (\Throwable) {
                // Fallback to async flow when immediate generation is unavailable.
                ProcessExternalOrderJob::dispatch($order->id);
            }
        } else {
            ProcessExternalOrderJob::dispatch($order->id);
        }

        return [
            'order' => $order->id,
            'status' => $this->statusMapper->map($order->status),
            'original_link' => $order->link,
            'quantity' => (int) $order->quantity,
            'charge' => number_format((float) $order->charge, 2, '.', ''),
            'currency' => $order->currency,
        ];
    }
}

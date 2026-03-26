<?php

namespace App\Services\PanelApi;

use App\Models\Order;

class PanelStatusMapper
{
    public function map(string $internalStatus): string
    {
        return match ($internalStatus) {
            Order::STATUS_PENDING,
            Order::STATUS_VALIDATING,
            Order::STATUS_ACCEPTED => 'Pending',
            Order::STATUS_IN_PROGRESS => 'In progress',
            Order::STATUS_PARTIAL => 'Partial',
            Order::STATUS_COMPLETED => 'Completed',
            Order::STATUS_FAILED,
            Order::STATUS_CANCELED => 'Canceled',
            Order::STATUS_REFUNDED => 'Refunded',
            default => 'Pending',
        };
    }
}

<?php

namespace App\Actions\PanelApi;

use App\Models\ExternalClient;

class HandlePanelMultipleStatusAction
{
    public function __construct(
        protected HandlePanelStatusAction $statusAction
    ) {}

    public function execute(ExternalClient $client, string $ordersInput): array
    {
        $ids = collect(explode(',', $ordersInput))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->values();

        if ($ids->isEmpty()) {
            return ['error' => 'Orders parameter is required'];
        }

        $result = [];
        foreach ($ids as $id) {
            $result[(string) $id] = $this->statusAction->execute($client, $id);
        }

        return $result;
    }
}

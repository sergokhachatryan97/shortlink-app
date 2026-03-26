<?php

namespace App\Actions\PanelApi;

use App\Models\ExternalService;

class HandlePanelServicesAction
{
    public function execute(): array
    {
        $services = ExternalService::query()
            ->with('category')
            ->where('is_active', true)
            ->where('is_external_available', true)
            ->orderBy('id')
            ->get();

        return $services->map(function (ExternalService $service) {
            return [
                'service' => $service->id,
                'name' => $service->name,
                'type' => 'default',
                'category' => $service->category?->name ?? 'General',
                'rate' => number_format((float) $service->rate, 4, '.', ''),
                'min' => (int) $service->min_quantity,
                'max' => (int) $service->max_quantity,
            ];
        })->values()->all();
    }
}

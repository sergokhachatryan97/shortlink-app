<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyRateService
{
    public function usdToRub(): float
    {
        return Cache::remember('usd_to_rub_rate', 3600, function () {
            try {
                $response = Http::timeout(5)->get('https://www.cbr-xml-daily.ru/daily_json.js');

                if ($response->successful()) {
                    $rate = $response->json('Valute.USD.Value');
                    if ($rate && $rate > 0) {
                        Log::info('Currency rate fetched from CBR', ['usd_to_rub' => $rate]);
                        return (float) $rate;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to fetch currency rate from CBR', ['error' => $e->getMessage()]);
            }

            return (float) config('services.yookassa.usd_to_rub', 90);
        });
    }

    public function convertUsdToRub(float $usd): float
    {
        return round($usd * $this->usdToRub(), 2);
    }
}

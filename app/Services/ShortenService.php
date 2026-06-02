<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ShortenService
{
    private string $baseUrl = 'http://162.55.199.246:8809/';

    /**
     * Generate shortened links via external API.
     *
     * @return array<int, string> Array of shortened URLs
     */
    public function shorten(string $url, int $count): array
    {
        $response = Http::get("{$this->baseUrl}/shorten", [
            'url' => $url,
            'count' => $count,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Shorten API request failed: ' . $response->body());
        }

        $links = $response->json();
        if (!is_array($links)) {
            throw new \RuntimeException('Shorten API returned invalid response');
        }

        return array_values(array_filter($links, fn ($v) => is_string($v)));
    }
}

<?php

namespace App\Services\WbApi;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class WbApiClient
{
    public function fetchPage(string $endpoint, array $params): array
    {
        $baseUrl = rtrim((string) config('wb-api.base_url'), '/');
        $key = (string) config('wb-api.key');

        $response = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->timeout((int) config('wb-api.timeout'))
            ->retry((int) config('wb-api.retry_times'), (int) config('wb-api.retry_sleep'))
            ->get('/api/'.$endpoint, $params + ['key' => $key]);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'WB API returned %s for %s: %s',
                $response->status(),
                $endpoint,
                $response->body()
            ));
        }

        $payload = $response->json();

        if (! is_array($payload) || ! array_key_exists('data', $payload)) {
            throw new RuntimeException('Unexpected response format for '.$endpoint);
        }

        return $payload;
    }
}

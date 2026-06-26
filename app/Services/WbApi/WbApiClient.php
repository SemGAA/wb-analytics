<?php

namespace App\Services\WbApi;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WbApiClient
{
    public function fetchPage(string $endpoint, array $params, WbCredentials $credentials, ?callable $debug = null): array
    {
        $baseUrl = rtrim((string) ($credentials->baseUrl ?: config('wb-api.base_url')), '/');
        $request = $this->request($baseUrl, $credentials, $params);
        $attempts = max((int) config('wb-api.retry_times'), 1);
        $sleep = max((int) config('wb-api.retry_sleep'), 1);
        $response = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            if ($debug !== null) {
                $debug(sprintf('GET /api/%s page=%s attempt=%d', $endpoint, $params['page'] ?? 1, $attempt));
            }

            $response = $request->get('/api/'.$endpoint, $params);

            if (! $this->isTooManyRequests($response) || $attempt === $attempts) {
                break;
            }

            $pause = $this->retryPause($response, $sleep, $attempt);
            if ($debug !== null) {
                $debug(sprintf('Too many requests on %s, retry in %d ms', $endpoint, $pause));
            }
            usleep($pause * 1000);
        }

        if ($response === null) {
            throw new RuntimeException('WB API request was not sent for '.$endpoint);
        }

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

    private function request(string $baseUrl, WbCredentials $credentials, array &$params): PendingRequest
    {
        $request = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->timeout((int) config('wb-api.timeout'));

        if ($credentials->type === 'bearer') {
            $token = $credentials->value('token');
            if ($token !== null) {
                return $request->withToken($token);
            }
        }

        if ($credentials->type === 'login-password') {
            $login = $credentials->credentials['login'] ?? null;
            $password = $credentials->credentials['password'] ?? null;
            if ($login !== null && $password !== null) {
                return $request->withBasicAuth((string) $login, (string) $password);
            }
        }

        $key = $credentials->value('key');
        if ($key !== null) {
            $params['key'] = $key;
        }

        return $request;
    }

    private function isTooManyRequests(Response $response): bool
    {
        return $response->status() === 429
            || str_contains(strtolower($response->body()), 'too many requests');
    }

    private function retryPause(Response $response, int $sleep, int $attempt): int
    {
        $retryAfter = $response->header('Retry-After');

        if (is_numeric($retryAfter)) {
            return max((int) $retryAfter * 1000, $sleep);
        }

        return $sleep * (2 ** ($attempt - 1));
    }
}

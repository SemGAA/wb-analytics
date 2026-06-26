<?php

namespace App\Services\WbApi;

class WbCredentials
{
    public function __construct(
        public readonly string $type,
        public readonly array $credentials,
        public readonly ?string $baseUrl = null,
    ) {}

    public function value(string $fallbackKey = 'key'): ?string
    {
        foreach ([$fallbackKey, 'token', 'value', 'password'] as $key) {
            if (isset($this->credentials[$key]) && $this->credentials[$key] !== '') {
                return (string) $this->credentials[$key];
            }
        }

        return null;
    }
}

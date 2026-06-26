<?php

namespace App\Console\Commands;

use App\Models\ApiService;
use App\Models\TokenType;
use Illuminate\Console\Command;

class AttachTokenTypeToApiService extends Command
{
    protected $signature = 'app:api-service:attach-token-type {service?} {type?}';

    protected $description = 'Allow a token type for an API service';

    public function handle(): int
    {
        $service = $this->service((string) ($this->argument('service') ?: $this->ask('Service id or code')));
        $type = $this->type((string) ($this->argument('type') ?: $this->ask('Token type id or code')));

        $service->tokenTypes()->syncWithoutDetaching([$type->id]);

        $this->info('Token type attached');

        return self::SUCCESS;
    }

    private function service(string $value): ApiService
    {
        $service = ApiService::query()
            ->where('id', ctype_digit($value) ? (int) $value : 0)
            ->orWhere('code', $value)
            ->first();

        if ($service === null) {
            $this->fail('API service not found: '.$value);
        }

        return $service;
    }

    private function type(string $value): TokenType
    {
        $type = TokenType::query()
            ->where('id', ctype_digit($value) ? (int) $value : 0)
            ->orWhere('code', $value)
            ->first();

        if ($type === null) {
            $this->fail('Token type not found: '.$value);
        }

        return $type;
    }
}

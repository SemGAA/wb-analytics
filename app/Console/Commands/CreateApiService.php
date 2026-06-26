<?php

namespace App\Console\Commands;

use App\Models\ApiService;
use Illuminate\Console\Command;

class CreateApiService extends Command
{
    protected $signature = 'app:api-service:create {code?} {--name=} {--base-url=}';

    protected $description = 'Create an API service';

    public function handle(): int
    {
        $code = (string) ($this->argument('code') ?: $this->ask('Service code'));
        $name = (string) ($this->option('name') ?: $this->ask('Service name', $code));

        $service = ApiService::query()->updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'base_url' => $this->option('base-url') ?: null,
                'is_active' => true,
            ],
        );

        $this->info('API service saved: '.$service->id);

        return self::SUCCESS;
    }
}

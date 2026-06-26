<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\ApiService;
use App\Models\ApiToken;
use App\Models\TokenType;
use Illuminate\Console\Command;

class CreateApiToken extends Command
{
    protected $signature = 'app:api-token:create
        {--account=}
        {--service=}
        {--type=api-key}
        {--name=}
        {--credential=}
        {--login=}';

    protected $description = 'Create or update an API token for an account';

    public function handle(): int
    {
        $account = $this->account((string) ($this->option('account') ?: $this->ask('Account id, name or external id')));
        $service = $this->service((string) ($this->option('service') ?: config('wb-api.service_code')));
        $type = $this->type((string) $this->option('type'));

        $service->tokenTypes()->syncWithoutDetaching([$type->id]);

        $token = ApiToken::query()->updateOrCreate(
            [
                'account_id' => $account->id,
                'api_service_id' => $service->id,
                'token_type_id' => $type->id,
            ],
            [
                'name' => $this->option('name') ?: null,
                'credentials' => $this->credentials($type->code),
                'is_active' => true,
            ],
        );

        $this->info('API token saved: '.$token->id);

        return self::SUCCESS;
    }

    private function account(string $value): Account
    {
        $account = Account::query()
            ->where('id', ctype_digit($value) ? (int) $value : 0)
            ->orWhere('name', $value)
            ->orWhere('external_id', $value)
            ->first();

        if ($account === null) {
            $this->fail('Account not found: '.$value);
        }

        return $account;
    }

    private function service(string $value): ApiService
    {
        return ApiService::query()->firstOrCreate(
            ['code' => $value],
            [
                'name' => $value,
                'base_url' => (string) config('wb-api.base_url'),
                'is_active' => true,
            ],
        );
    }

    private function type(string $value): TokenType
    {
        return TokenType::query()->firstOrCreate(
            ['code' => $value],
            ['name' => $value],
        );
    }

    private function credentials(string $type): array
    {
        if ($type === 'login-password') {
            return [
                'login' => (string) ($this->option('login') ?: $this->ask('Login')),
                'password' => (string) ($this->option('credential') ?: $this->secret('Password')),
            ];
        }

        $credential = (string) ($this->option('credential') ?: $this->secret('Credential'));
        $json = json_decode($credential, true);

        if (is_array($json)) {
            return $json;
        }

        if ($type === 'bearer') {
            return ['token' => $credential];
        }

        if ($type === 'api-key') {
            return ['key' => $credential];
        }

        return ['value' => $credential];
    }
}

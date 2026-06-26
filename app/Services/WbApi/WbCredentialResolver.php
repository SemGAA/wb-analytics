<?php

namespace App\Services\WbApi;

use App\Models\Account;
use App\Models\ApiService;
use App\Models\Company;
use App\Models\TokenType;
use RuntimeException;

class WbCredentialResolver
{
    public function ensureDefaults(): void
    {
        $service = ApiService::query()->firstOrCreate(
            ['code' => (string) config('wb-api.service_code')],
            [
                'name' => (string) config('wb-api.service_name'),
                'base_url' => (string) config('wb-api.base_url'),
            ],
        );

        $type = TokenType::query()->firstOrCreate(
            ['code' => 'api-key'],
            ['name' => 'API key'],
        );

        $service->tokenTypes()->syncWithoutDetaching([$type->id]);
    }

    public function defaultAccount(): Account
    {
        $company = Company::query()->firstOrCreate(
            ['code' => 'default'],
            ['name' => (string) config('wb-api.default_company')],
        );

        return Account::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'external_id' => 'default',
            ],
            [
                'name' => (string) config('wb-api.default_account'),
                'is_active' => true,
            ],
        );
    }

    public function resolve(Account $account): WbCredentials
    {
        $service = ApiService::query()
            ->where('code', (string) config('wb-api.service_code'))
            ->first();

        $token = $service?->apiTokens()
            ->with(['apiService', 'tokenType'])
            ->where('account_id', $account->id)
            ->where('is_active', true)
            ->first();

        if ($token !== null) {
            return new WbCredentials(
                type: (string) $token->tokenType->code,
                credentials: (array) $token->credentials,
                baseUrl: $token->apiService->base_url,
            );
        }

        $key = config('wb-api.key');
        if (filled($key)) {
            return new WbCredentials('api-key', ['key' => (string) $key]);
        }

        throw new RuntimeException('WB API token is missing for account '.$account->id.'. Add it with app:api-token:create.');
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateAccount extends Command
{
    protected $signature = 'app:account:create {name?} {--company=} {--external-id=}';

    protected $description = 'Create an account for a company';

    public function handle(): int
    {
        $company = $this->company((string) ($this->option('company') ?: $this->ask('Company id, code or name')));
        $name = (string) ($this->argument('name') ?: $this->ask('Account name'));

        $account = Account::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'name' => $name,
            ],
            [
                'external_id' => $this->option('external-id') ?: null,
                'is_active' => true,
            ],
        );

        $this->info('Account saved: '.$account->id);

        return self::SUCCESS;
    }

    private function company(string $value): Company
    {
        $company = Company::query()
            ->where('id', ctype_digit($value) ? (int) $value : 0)
            ->orWhere('code', $value)
            ->orWhere('name', $value)
            ->first();

        if ($company !== null) {
            return $company;
        }

        return Company::query()->create([
            'name' => $value,
            'code' => Str::slug($value) ?: null,
        ]);
    }
}

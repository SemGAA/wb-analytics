<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class CreateCompany extends Command
{
    protected $signature = 'app:company:create {name?} {--code=}';

    protected $description = 'Create a company';

    public function handle(): int
    {
        $name = (string) ($this->argument('name') ?: $this->ask('Company name'));
        $code = $this->option('code') ?: null;

        $company = $code
            ? Company::query()->updateOrCreate(['code' => $code], ['name' => $name])
            : Company::query()->firstOrCreate(['name' => $name]);

        $this->info('Company saved: '.$company->id);

        return self::SUCCESS;
    }
}

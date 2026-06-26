<?php

namespace App\Console\Commands;

use App\Models\TokenType;
use Illuminate\Console\Command;

class CreateTokenType extends Command
{
    protected $signature = 'app:token-type:create {code?} {--name=}';

    protected $description = 'Create a token type';

    public function handle(): int
    {
        $code = (string) ($this->argument('code') ?: $this->ask('Token type code'));
        $name = (string) ($this->option('name') ?: $this->ask('Token type name', $code));

        $type = TokenType::query()->updateOrCreate(['code' => $code], ['name' => $name]);

        $this->info('Token type saved: '.$type->id);

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Services\WbApi\WbCredentialResolver;
use App\Services\WbApi\WbDataImporter;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class SyncWbApiData extends Command
{
    protected $signature = 'wb:sync
        {--endpoint=* : sales, orders, stocks, incomes}
        {--account= : Account id, name or external id}
        {--all-accounts : Load all active accounts}
        {--from= : Start date in Y-m-d format}
        {--to= : End date in Y-m-d format}
        {--limit= : Rows per request, max 500}
        {--max-pages= : Limit pages for a quick test run}
        {--sleep=0 : Pause between requests in milliseconds}
        {--fresh : Clear target account rows before loading}
        {--fresh-only : Start from the latest saved date for the account}';

    protected $description = 'Load sales, orders, stocks and incomes from WB test API into MySQL';

    public function handle(WbDataImporter $importer, WbCredentialResolver $credentialResolver): int
    {
        $credentialResolver->ensureDefaults();
        $accounts = $this->accounts($credentialResolver);
        $endpoints = $this->endpoints();
        $limit = min(max((int) ($this->option('limit') ?: config('wb-api.limit')), 1), 500);
        $maxPages = $this->option('max-pages') !== null ? (int) $this->option('max-pages') : null;
        $sleep = max((int) $this->option('sleep'), 0);

        foreach ($accounts as $account) {
            $credentials = $credentialResolver->resolve($account);

            foreach ($endpoints as $endpoint) {
                [$from, $to] = $this->dateRange($importer, $account->id, $endpoint);

                if ($this->option('fresh')) {
                    $importer->clearAccountEndpoint($account->id, $endpoint);
                }

                $logId = DB::table('api_sync_logs')->insertGetId([
                    'account_id' => $account->id,
                    'endpoint' => $endpoint,
                    'date_from' => $from,
                    'date_to' => $to,
                    'status' => 'running',
                    'started_at' => now()->toDateTimeString(),
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ]);

                try {
                    $this->info(sprintf(
                        'Loading account %d, %s from %s%s',
                        $account->id,
                        $endpoint,
                        $from,
                        $to ? ' to '.$to : ''
                    ));

                    $result = $importer->import(
                        accountId: $account->id,
                        endpoint: $endpoint,
                        from: $from,
                        to: $to,
                        limit: $limit,
                        credentials: $credentials,
                        maxPages: $maxPages,
                        sleep: $sleep,
                        progress: function (string $endpoint, int $page, int $lastPage, int $pageRows, int $totalRows) use ($account): void {
                            $this->line(sprintf(
                                'account %d %s page %d/%d, page rows: %d, total rows: %d',
                                $account->id,
                                $endpoint,
                                $page,
                                $lastPage,
                                $pageRows,
                                $totalRows
                            ));
                        },
                        debug: $this->output->isVerbose() ? fn (string $message) => $this->line('[debug] '.$message) : null,
                    );

                    DB::table('api_sync_logs')->where('id', $logId)->update([
                        'pages' => $result['pages'],
                        'rows' => $result['rows'],
                        'status' => 'success',
                        'finished_at' => now()->toDateTimeString(),
                        'updated_at' => now()->toDateTimeString(),
                    ]);

                    $this->info(sprintf('Done account %d, %s: %d rows in %d pages', $account->id, $endpoint, $result['rows'], $result['pages']));
                } catch (Throwable $exception) {
                    DB::table('api_sync_logs')->where('id', $logId)->update([
                        'status' => 'failed',
                        'error' => $exception->getMessage(),
                        'finished_at' => now()->toDateTimeString(),
                        'updated_at' => now()->toDateTimeString(),
                    ]);

                    $this->error($exception->getMessage());

                    return self::FAILURE;
                }
            }
        }

        return self::SUCCESS;
    }

    private function accounts(WbCredentialResolver $credentialResolver): Collection
    {
        if ($this->option('account') !== null && $this->option('all-accounts')) {
            $this->fail('Use either --account or --all-accounts.');
        }

        if ($this->option('account') !== null) {
            $account = $this->findAccount((string) $this->option('account'));

            if ($account === null) {
                $this->fail('Account not found: '.$this->option('account'));
            }

            return collect([$account]);
        }

        if ($this->option('all-accounts')) {
            $accounts = Account::query()->where('is_active', true)->orderBy('id')->get();

            if ($accounts->isNotEmpty()) {
                return $accounts;
            }
        }

        if (filled(config('wb-api.key'))) {
            return collect([$credentialResolver->defaultAccount()]);
        }

        $account = Account::query()->where('is_active', true)->orderBy('id')->first();

        if ($account === null) {
            $this->fail('No active accounts found. Create one with app:account:create.');
        }

        return collect([$account]);
    }

    private function findAccount(string $value): ?Account
    {
        return Account::query()
            ->where('id', ctype_digit($value) ? (int) $value : 0)
            ->orWhere('name', $value)
            ->orWhere('external_id', $value)
            ->first();
    }

    private function endpoints(): array
    {
        $allowed = config('wb-api.endpoints');
        $selected = array_filter((array) $this->option('endpoint'));

        if ($selected === []) {
            return $allowed;
        }

        $unknown = array_diff($selected, $allowed);
        if ($unknown !== []) {
            $this->fail('Unknown endpoints: '.implode(', ', $unknown));
        }

        return array_values($selected);
    }

    private function dateRange(WbDataImporter $importer, int $accountId, string $endpoint): array
    {
        $today = Carbon::today()->toDateString();
        $fallback = $endpoint === 'stocks' ? $today : (string) config('wb-api.default_from');
        $from = $this->option('from') ?: (
            $this->option('fresh')
                ? $fallback
                : ($this->option('fresh-only')
                ? $importer->freshFromDate($accountId, $endpoint, $fallback)
                : $fallback)
        );
        $to = $endpoint === 'stocks' ? null : ($this->option('to') ?: $today);

        return [$from, $to];
    }
}

<?php

namespace App\Console\Commands;

use App\Services\WbApi\WbApiRecordMapper;
use App\Services\WbApi\WbDataImporter;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SyncWbApiData extends Command
{
    protected $signature = 'wb:sync
        {--endpoint=* : sales, orders, stocks, incomes}
        {--from= : Start date in Y-m-d format}
        {--to= : End date in Y-m-d format}
        {--limit= : Rows per request, max 500}
        {--max-pages= : Limit pages for a quick test run}
        {--sleep=0 : Pause between requests in milliseconds}
        {--fresh : Clear target tables before loading}';

    protected $description = 'Load sales, orders, stocks and incomes from WB test API into MySQL';

    public function handle(WbDataImporter $importer, WbApiRecordMapper $mapper): int
    {
        if (blank(config('wb-api.key'))) {
            $this->error('WB_API_KEY is empty. Set it in .env before running import.');

            return self::FAILURE;
        }

        $endpoints = $this->endpoints();
        $limit = min(max((int) ($this->option('limit') ?: config('wb-api.limit')), 1), 500);
        $maxPages = $this->option('max-pages') !== null ? (int) $this->option('max-pages') : null;
        $sleep = max((int) $this->option('sleep'), 0);

        foreach ($endpoints as $endpoint) {
            [$from, $to] = $this->dateRange($endpoint);
            $table = $mapper->tableFor($endpoint);

            if ($this->option('fresh')) {
                DB::table($table)->delete();
            }

            $logId = DB::table('api_sync_logs')->insertGetId([
                'endpoint' => $endpoint,
                'date_from' => $from,
                'date_to' => $to,
                'status' => 'running',
                'started_at' => now()->toDateTimeString(),
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]);

            try {
                $this->info(sprintf('Loading %s from %s%s', $endpoint, $from, $to ? ' to '.$to : ''));

                $result = $importer->import(
                    endpoint: $endpoint,
                    from: $from,
                    to: $to,
                    limit: $limit,
                    maxPages: $maxPages,
                    sleep: $sleep,
                    progress: function (string $endpoint, int $page, int $lastPage, int $pageRows, int $totalRows): void {
                        $this->line(sprintf(
                            '%s page %d/%d, page rows: %d, total rows: %d',
                            $endpoint,
                            $page,
                            $lastPage,
                            $pageRows,
                            $totalRows
                        ));
                    },
                );

                DB::table('api_sync_logs')->where('id', $logId)->update([
                    'pages' => $result['pages'],
                    'rows' => $result['rows'],
                    'status' => 'success',
                    'finished_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ]);

                $this->info(sprintf('Done %s: %d rows in %d pages', $endpoint, $result['rows'], $result['pages']));
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

        return self::SUCCESS;
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

    private function dateRange(string $endpoint): array
    {
        $today = Carbon::today()->toDateString();
        $from = $this->option('from') ?: ($endpoint === 'stocks' ? $today : config('wb-api.default_from'));
        $to = $endpoint === 'stocks' ? null : ($this->option('to') ?: $today);

        return [$from, $to];
    }
}

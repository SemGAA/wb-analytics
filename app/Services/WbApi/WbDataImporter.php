<?php

namespace App\Services\WbApi;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class WbDataImporter
{
    public function __construct(
        private readonly WbApiClient $client,
        private readonly WbApiRecordMapper $mapper,
    ) {}

    public function import(
        string $endpoint,
        string $from,
        ?string $to,
        int $limit,
        ?int $maxPages = null,
        int $sleep = 0,
        ?callable $progress = null,
    ): array {
        $table = $this->mapper->tableFor($endpoint);
        $page = 1;
        $lastPage = 1;
        $pages = 0;
        $rows = 0;

        do {
            $payload = $this->client->fetchPage($endpoint, $this->requestParams($endpoint, $from, $to, $page, $limit));
            $items = $payload['data'] ?? [];
            $lastPage = (int) ($payload['meta']['last_page'] ?? $page);

            $mappedRows = array_map(fn (array $item) => $this->mapper->map($endpoint, $item), $items);

            if ($mappedRows !== []) {
                if (! config('wb-api.store_payload')) {
                    $mappedRows = array_map(function (array $row): array {
                        unset($row['payload']);

                        return $row;
                    }, $mappedRows);
                }

                foreach (array_chunk($mappedRows, (int) config('wb-api.db_batch')) as $chunk) {
                    $this->insertChunk($table, $chunk);
                }
            }

            $pages++;
            $rows += count($mappedRows);

            if ($progress !== null) {
                $progress($endpoint, $page, $lastPage, count($mappedRows), $rows);
            }

            if ($maxPages !== null && $pages >= $maxPages) {
                break;
            }

            if ($sleep > 0 && $page < $lastPage) {
                usleep($sleep * 1000);
            }

            $page++;
        } while ($page <= $lastPage);

        return [
            'endpoint' => $endpoint,
            'pages' => $pages,
            'rows' => $rows,
            'last_page' => $lastPage,
        ];
    }

    private function requestParams(string $endpoint, string $from, ?string $to, int $page, int $limit): array
    {
        $params = [
            'dateFrom' => $from,
            'page' => $page,
            'limit' => $limit,
        ];

        if ($endpoint !== 'stocks') {
            $params['dateTo'] = $to;
        }

        return $params;
    }

    private function insertChunk(string $table, array $chunk): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            DB::table($table)->insertOrIgnore($chunk);

            return;
        }

        $sql = $this->insertIgnoreSql($table, $chunk);
        $attempt = 0;

        while (true) {
            try {
                DB::unprepared($sql);

                return;
            } catch (QueryException $exception) {
                DB::disconnect();

                $attempt++;
                if ($attempt >= 3) {
                    throw $exception;
                }
            }
        }
    }

    private function insertIgnoreSql(string $table, array $chunk): string
    {
        $columns = array_keys($chunk[0]);
        $columnList = implode(', ', array_map(fn (string $column) => '`'.str_replace('`', '``', $column).'`', $columns));
        $values = [];

        foreach ($chunk as $row) {
            $values[] = '('.implode(', ', array_map(fn (mixed $value) => $this->quote($value), array_values($row))).')';
        }

        return sprintf(
            'INSERT IGNORE INTO `%s` (%s) VALUES %s',
            str_replace('`', '``', $table),
            $columnList,
            implode(', ', $values),
        );
    }

    private function quote(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return DB::connection()->getPdo()->quote((string) $value);
    }
}

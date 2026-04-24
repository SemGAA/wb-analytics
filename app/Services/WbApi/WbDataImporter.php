<?php

namespace App\Services\WbApi;

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
                foreach (array_chunk($mappedRows, (int) config('wb-api.db_batch')) as $chunk) {
                    DB::table($table)->upsert(
                        $chunk,
                        ['source_key'],
                        array_values(array_diff(array_keys($chunk[0]), ['source_key', 'created_at']))
                    );
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
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WbSyncCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_loads_sales_page_to_database(): void
    {
        config([
            'wb-api.base_url' => 'http://wb.test',
            'wb-api.key' => 'test-key',
        ]);

        Http::fake([
            'wb.test/*' => Http::response([
                'data' => [
                    [
                        'g_number' => '460884615758287837',
                        'date' => '2025-06-28',
                        'last_change_date' => '2025-06-28',
                        'supplier_article' => '972f91ed2040d71a',
                        'tech_size' => '66e7dff9f98764da',
                        'barcode' => 376357806,
                        'total_price' => '5676',
                        'discount_percent' => '32',
                        'is_supply' => false,
                        'is_realization' => true,
                        'promo_code_discount' => null,
                        'warehouse_name' => 'Электросталь',
                        'country_name' => 'Россия',
                        'oblast_okrug_name' => 'Центральный федеральный округ',
                        'region_name' => 'Ярославская область',
                        'income_id' => 29077360,
                        'sale_id' => 'S17613661853',
                        'odid' => null,
                        'spp' => '22',
                        'for_pay' => '1471.9',
                        'finished_price' => '1400',
                        'price_with_disc' => '1795',
                        'nm_id' => 353001876,
                        'subject' => '716cae14263ef4b7',
                        'category' => '9f463620982b6cc9',
                        'brand' => 'a66c77274e96b48c',
                        'is_storno' => null,
                    ],
                ],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 500,
                    'total' => 1,
                ],
            ]),
        ]);

        $this->artisan('wb:sync', [
            '--endpoint' => ['sales'],
            '--from' => '2025-06-28',
            '--to' => '2025-06-28',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('wb_sales', [
            'sale_id' => 'S17613661853',
            'warehouse_name' => 'Электросталь',
            'nm_id' => 353001876,
        ]);

        $this->assertDatabaseHas('api_sync_logs', [
            'endpoint' => 'sales',
            'status' => 'success',
            'rows' => 1,
        ]);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/api/sales')
            && str_contains($request->url(), 'key=test-key'));
    }
}

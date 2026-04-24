<?php

namespace Tests\Unit;

use App\Services\WbApi\WbApiRecordMapper;
use PHPUnit\Framework\TestCase;

class WbApiRecordMapperTest extends TestCase
{
    public function test_it_maps_order_dates_and_booleans(): void
    {
        $mapper = new WbApiRecordMapper;

        $row = $mapper->map('orders', [
            'g_number' => '6072454924839546719',
            'date' => '2026-01-24 23:13:55',
            'last_change_date' => '2026-01-25',
            'supplier_article' => 'f1f104fae6d65eb1',
            'tech_size' => '66e7dff9f98764da',
            'barcode' => 137928016,
            'total_price' => '1492.1',
            'discount_percent' => 18,
            'warehouse_name' => 'Электросталь',
            'oblast' => 'Москва',
            'income_id' => 35870156,
            'odid' => '0',
            'nm_id' => 959794489,
            'subject' => 'dd6ad32ea8eeb9eb',
            'category' => '9f463620982b6cc9',
            'brand' => 'e129baf5351375dd',
            'is_cancel' => false,
            'cancel_dt' => null,
        ]);

        $this->assertSame('2026-01-24 23:13:55', $row['date']);
        $this->assertSame('2026-01-25 00:00:00', $row['last_change_date']);
        $this->assertFalse($row['is_cancel']);
        $this->assertSame('137928016', $row['barcode']);
        $this->assertArrayHasKey('source_key', $row);
        $this->assertArrayHasKey('payload', $row);
    }
}

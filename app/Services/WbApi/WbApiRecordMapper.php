<?php

namespace App\Services\WbApi;

use Carbon\Carbon;
use InvalidArgumentException;

class WbApiRecordMapper
{
    public function tableFor(string $endpoint): string
    {
        $table = config('wb-api.tables.'.$endpoint);

        if (! is_string($table) || $table === '') {
            throw new InvalidArgumentException('Unknown endpoint '.$endpoint);
        }

        return $table;
    }

    public function map(string $endpoint, array $item): array
    {
        return match ($endpoint) {
            'sales' => $this->mapSale($item),
            'orders' => $this->mapOrder($item),
            'stocks' => $this->mapStock($item),
            'incomes' => $this->mapIncome($item),
            default => throw new InvalidArgumentException('Unknown endpoint '.$endpoint),
        };
    }

    private function mapSale(array $item): array
    {
        return $this->withBaseColumns('sales', $item, [
            'g_number' => $this->str($item['g_number'] ?? null),
            'date' => $this->date($item['date'] ?? null),
            'last_change_date' => $this->dateTime($item['last_change_date'] ?? null),
            'supplier_article' => $this->str($item['supplier_article'] ?? null),
            'tech_size' => $this->str($item['tech_size'] ?? null),
            'barcode' => $this->str($item['barcode'] ?? null),
            'total_price' => $this->decimal($item['total_price'] ?? null),
            'discount_percent' => $this->decimal($item['discount_percent'] ?? null),
            'is_supply' => $this->bool($item['is_supply'] ?? null),
            'is_realization' => $this->bool($item['is_realization'] ?? null),
            'promo_code_discount' => $this->decimal($item['promo_code_discount'] ?? null),
            'warehouse_name' => $this->str($item['warehouse_name'] ?? null),
            'country_name' => $this->str($item['country_name'] ?? null),
            'oblast_okrug_name' => $this->str($item['oblast_okrug_name'] ?? null),
            'region_name' => $this->str($item['region_name'] ?? null),
            'income_id' => $this->int($item['income_id'] ?? null),
            'sale_id' => $this->str($item['sale_id'] ?? null),
            'odid' => $this->str($item['odid'] ?? null),
            'spp' => $this->decimal($item['spp'] ?? null),
            'for_pay' => $this->decimal($item['for_pay'] ?? null),
            'finished_price' => $this->decimal($item['finished_price'] ?? null),
            'price_with_disc' => $this->decimal($item['price_with_disc'] ?? null),
            'nm_id' => $this->int($item['nm_id'] ?? null),
            'subject' => $this->str($item['subject'] ?? null),
            'category' => $this->str($item['category'] ?? null),
            'brand' => $this->str($item['brand'] ?? null),
            'is_storno' => $this->bool($item['is_storno'] ?? null),
        ]);
    }

    private function mapOrder(array $item): array
    {
        return $this->withBaseColumns('orders', $item, [
            'g_number' => $this->str($item['g_number'] ?? null),
            'date' => $this->dateTime($item['date'] ?? null),
            'last_change_date' => $this->dateTime($item['last_change_date'] ?? null),
            'supplier_article' => $this->str($item['supplier_article'] ?? null),
            'tech_size' => $this->str($item['tech_size'] ?? null),
            'barcode' => $this->str($item['barcode'] ?? null),
            'total_price' => $this->decimal($item['total_price'] ?? null),
            'discount_percent' => $this->decimal($item['discount_percent'] ?? null),
            'warehouse_name' => $this->str($item['warehouse_name'] ?? null),
            'oblast' => $this->str($item['oblast'] ?? null),
            'income_id' => $this->int($item['income_id'] ?? null),
            'odid' => $this->str($item['odid'] ?? null),
            'nm_id' => $this->int($item['nm_id'] ?? null),
            'subject' => $this->str($item['subject'] ?? null),
            'category' => $this->str($item['category'] ?? null),
            'brand' => $this->str($item['brand'] ?? null),
            'is_cancel' => $this->bool($item['is_cancel'] ?? null),
            'cancel_dt' => $this->dateTime($item['cancel_dt'] ?? null),
        ]);
    }

    private function mapStock(array $item): array
    {
        return $this->withBaseColumns('stocks', $item, [
            'date' => $this->date($item['date'] ?? null),
            'last_change_date' => $this->dateTime($item['last_change_date'] ?? null),
            'supplier_article' => $this->str($item['supplier_article'] ?? null),
            'tech_size' => $this->str($item['tech_size'] ?? null),
            'barcode' => $this->str($item['barcode'] ?? null),
            'quantity' => $this->int($item['quantity'] ?? null),
            'is_supply' => $this->bool($item['is_supply'] ?? null),
            'is_realization' => $this->bool($item['is_realization'] ?? null),
            'quantity_full' => $this->int($item['quantity_full'] ?? null),
            'warehouse_name' => $this->str($item['warehouse_name'] ?? null),
            'in_way_to_client' => $this->int($item['in_way_to_client'] ?? null),
            'in_way_from_client' => $this->int($item['in_way_from_client'] ?? null),
            'nm_id' => $this->int($item['nm_id'] ?? null),
            'subject' => $this->str($item['subject'] ?? null),
            'category' => $this->str($item['category'] ?? null),
            'brand' => $this->str($item['brand'] ?? null),
            'sc_code' => $this->str($item['sc_code'] ?? null),
            'price' => $this->decimal($item['price'] ?? null),
            'discount' => $this->decimal($item['discount'] ?? null),
        ]);
    }

    private function mapIncome(array $item): array
    {
        return $this->withBaseColumns('incomes', $item, [
            'income_id' => $this->int($item['income_id'] ?? null),
            'number' => $this->str($item['number'] ?? null),
            'date' => $this->date($item['date'] ?? null),
            'last_change_date' => $this->dateTime($item['last_change_date'] ?? null),
            'supplier_article' => $this->str($item['supplier_article'] ?? null),
            'tech_size' => $this->str($item['tech_size'] ?? null),
            'barcode' => $this->str($item['barcode'] ?? null),
            'quantity' => $this->int($item['quantity'] ?? null),
            'total_price' => $this->decimal($item['total_price'] ?? null),
            'date_close' => $this->date($item['date_close'] ?? null),
            'warehouse_name' => $this->str($item['warehouse_name'] ?? null),
            'nm_id' => $this->int($item['nm_id'] ?? null),
        ]);
    }

    private function withBaseColumns(string $endpoint, array $item, array $columns): array
    {
        $now = Carbon::now()->toDateTimeString();

        return [
            'source_key' => $this->sourceKey($endpoint, $item),
            ...$columns,
            'payload' => json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function sourceKey(string $endpoint, array $item): string
    {
        $keys = match ($endpoint) {
            'sales' => ['sale_id', 'g_number', 'date', 'barcode', 'nm_id'],
            'orders' => ['g_number', 'date', 'barcode', 'nm_id', 'income_id'],
            'stocks' => ['date', 'warehouse_name', 'barcode', 'nm_id', 'supplier_article', 'tech_size'],
            'incomes' => ['income_id', 'date', 'barcode', 'nm_id', 'warehouse_name', 'supplier_article', 'tech_size'],
        };

        $source = [];
        foreach ($keys as $key) {
            $source[$key] = $item[$key] ?? null;
        }

        return hash('sha256', $endpoint.'|'.json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function str(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    private function int(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function decimal(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }

    private function bool(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    }

    private function date(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : Carbon::parse($value)->format('Y-m-d');
    }

    private function dateTime(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}

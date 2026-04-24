<?php

return [
    'base_url' => env('WB_API_BASE_URL', 'http://109.73.206.144:6969'),
    'key' => env('WB_API_KEY'),
    'limit' => (int) env('WB_API_LIMIT', 500),
    'db_batch' => (int) env('WB_DB_BATCH', 15),
    'store_payload' => (bool) env('WB_STORE_PAYLOAD', false),
    'timeout' => (int) env('WB_API_TIMEOUT', 30),
    'retry_times' => (int) env('WB_API_RETRY_TIMES', 3),
    'retry_sleep' => (int) env('WB_API_RETRY_SLEEP', 500),
    'default_from' => env('WB_API_DEFAULT_FROM', '2024-01-01'),

    'endpoints' => ['sales', 'orders', 'stocks', 'incomes'],

    'tables' => [
        'sales' => 'wb_sales',
        'orders' => 'wb_orders',
        'stocks' => 'wb_stocks',
        'incomes' => 'wb_incomes',
    ],
];

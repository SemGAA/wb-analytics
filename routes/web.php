<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $tables = config('wb-api.tables');
    $counts = [];

    foreach ($tables as $endpoint => $table) {
        $counts[$endpoint] = DB::table($table)->count();
    }

    return response()->json([
        'status' => 'ok',
        'tables' => $tables,
        'rows' => $counts,
    ]);
});

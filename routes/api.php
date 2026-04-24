<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/stats', function () {
    $tables = config('wb-api.tables');
    $counts = [];

    foreach ($tables as $endpoint => $table) {
        $counts[$endpoint] = DB::table($table)->count();
    }

    return response()->json([
        'tables' => $tables,
        'rows' => $counts,
    ]);
});

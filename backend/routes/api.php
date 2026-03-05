<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Orderly API v1',
        'version' => '1.0.0',
    ]);
});

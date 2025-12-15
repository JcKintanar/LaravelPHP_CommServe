<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'CommServe API - Laravel + Supabase',
        'status' => 'running',
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String()
    ]);
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

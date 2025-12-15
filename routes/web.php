<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return response()->json([
        'message' => 'CommServe API - Laravel + MySQL',
        'status' => 'running',
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String()
    ]);
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::get('/test-db', function () {
    try {
        // Test database connection
        DB::connection()->getPdo();
        
        // Try to fetch a user count
        $userCount = DB::table('users')->count();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Database connection working!',
            'database' => config('database.connections.mysql.database'),
            'user_count' => $userCount
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Database connection failed',
            'error' => $e->getMessage()
        ], 500);
    }
});

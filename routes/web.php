<?php

use Illuminate\Support\Facades\Route;
use App\Services\SupabaseService;

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

Route::get('/test-supabase', function () {
    try {
        $supabase = new SupabaseService();
        
        // Test connection by fetching users count
        $response = $supabase->from('users')
            ->select('id')
            ->limit(1)
            ->get();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Supabase connection working!',
            'supabase_url' => config('services.supabase.url'),
            'has_data' => !empty($response)
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Supabase connection failed',
            'error' => $e->getMessage()
        ], 500);
    }
});

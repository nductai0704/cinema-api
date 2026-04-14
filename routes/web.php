<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/setup', function () {
    try {
        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('optimize:clear');
        return response()->json([
            'message' => 'Database migrated and cache cleared successfully!',
            'output' => Artisan::output()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::get('/seed', function () {
    try {
        Artisan::call('db:seed', ['--force' => true]);
        return response()->json([
            'message' => 'Database seeded successfully!',
            'output' => Artisan::output()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
});

<?php

use App\Http\Controllers\Api\AnalyticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| EDWH Analytics API Routes
|--------------------------------------------------------------------------
| All routes are prefixed with /api automatically by Laravel.
| Protected by 'role:admin,viewer' RBAC middleware.
|
| NOTE: For the PoC, we skip JWT auth and use a simple API key header
| to make local testing straightforward. See RoleMiddleware for production config.
*/

// Public health check
Route::get('/ping', fn() => response()->json(['status' => 'ok', 'service' => 'EDWH Analytics API', 'version' => '1.0']));

// Analytics endpoints — accessible to both admin and viewer
Route::prefix('analytics')->group(function () {

    // Summary KPIs
    Route::get('/summary', [AnalyticsController::class, 'summary'])
        ->name('analytics.summary');

    // Time-series transaction chart data
    Route::get('/transactions', [AnalyticsController::class, 'transactions'])
        ->name('analytics.transactions');

    // AI anomaly reports
    Route::get('/anomalies', [AnalyticsController::class, 'anomalies'])
        ->name('analytics.anomalies');

    // Star-schema sales data mart
    Route::get('/data-mart/sales', [AnalyticsController::class, 'salesMart'])
        ->name('analytics.data-mart.sales');

});

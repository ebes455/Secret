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

    // UDW Research results (reads Python-generated CSV/JSON files)
    Route::get('/udw-research', function () {
        $base = base_path('results');

        $readCsv = function ($file) use ($base) {
            $path = $base . DIRECTORY_SEPARATOR . $file;
            if (!file_exists($path))
                return [];
            $rows = array_map('str_getcsv', file($path));
            $header = array_shift($rows);
            return array_map(fn($r) => array_combine($header, array_pad($r, count($header), null)), $rows);
        };

        $readJson = function ($file) use ($base) {
            $path = $base . DIRECTORY_SEPARATOR . $file;
            return file_exists($path) ? json_decode(file_get_contents($path), true) : [];
        };

        return response()->json([
            'benchmark' => $readCsv('benchmark_results.csv'),
            'scalability' => $readCsv('scalability_results.csv'),
            'dq' => $readCsv('data_quality_results.csv'),
            'etl' => $readCsv('etl_results.csv'),
            'anomaly' => $readJson('anomaly_metrics.json'),
            'prediction' => $readJson('prediction_metrics.json'),
        ]);
    })->name('analytics.udw-research');

});

<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard
Route::get('/', fn() => redirect('/dashboard'));

// Main BI Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');


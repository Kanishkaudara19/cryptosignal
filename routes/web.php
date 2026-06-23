<?php

// routes/web.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SignalController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Main dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/recent-signals', [DashboardController::class, 'recentSignals'])->name('dashboard.recent-signals');
Route::get('/dashboard/auto-signal-alert', [DashboardController::class, 'autoSignalAlert'])->name('dashboard.auto-signal-alert');

// Signal routes
Route::prefix('signals')->name('signals.')->group(function () {
    Route::get('/', [SignalController::class, 'index'])->name('index');
    Route::get('/{signal}', [SignalController::class, 'show'])->name('show');
});

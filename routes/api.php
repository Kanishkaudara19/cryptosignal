<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\SignalController;
use App\Http\Controllers\IndicatorController;

// ── Market data ──────────────────────────────────────────────────────────
Route::prefix('market')->group(function () {
    Route::get('/price/{symbol}',     [MarketController::class, 'price']);
    Route::get('/candles/{symbol}',   [MarketController::class, 'candles']);
    Route::get('/orderbook/{symbol}', [MarketController::class, 'orderBook']);
    Route::get('/coins',              [MarketController::class, 'coins']);
    Route::post('/refresh-all',       [MarketController::class, 'refreshAll']);
});

// ── Indicators ───────────────────────────────────────────────────────────
Route::prefix('indicators')->group(function () {
    Route::get('/{symbol}', [IndicatorController::class, 'show']);
});

// ── Signals ──────────────────────────────────────────────────────────────
Route::prefix('signals')->group(function () {
    Route::post('/generate',        [SignalController::class, 'generate']);
    Route::get('/history',          [SignalController::class, 'history']);
    Route::post('/{signal}/close',  [SignalController::class, 'close']);
});

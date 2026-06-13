<?php

namespace App\Http\Controllers;

use App\Models\Coin;
use App\Models\Signal;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $coins = Coin::active()
            ->orderBy('symbol')
            ->get();

        $recentSignals = Signal::with('coin')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $timeframes = [
            '1m'  => '1 minute',
            '5m'  => '5 minutes',
            '15m' => '15 minutes',
            '1h'  => '1 hour',
            '4h'  => '4 hours',
            '1d'  => '1 day',
            '1w'  => '1 week',
        ];

        return view('dashboard.index', compact('coins', 'recentSignals', 'timeframes'));
    }
}

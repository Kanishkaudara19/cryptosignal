<?php

namespace App\Http\Controllers;

use App\Models\Signal;
use App\Services\BinanceService;
use App\Services\SignalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SignalController extends Controller
{
    private SignalService  $signals;
    private BinanceService $binance;

    public function __construct(SignalService $signals, BinanceService $binance)
    {
        $this->signals = $signals;
        $this->binance = $binance;
    }

    /**
     * GET /signals
     * Filterable by symbol, interval, trade_type, status, signal_strength.
     */
    public function index(Request $request): View
    {
        $query = Signal::orderByDesc('created_at');

        if ($request->filled('symbol'))
            $query->where('symbol', strtoupper($request->symbol));

        if ($request->filled('interval'))
            $query->where('interval', $request->interval);

        if ($request->filled('trade_type'))
            $query->where('trade_type', $request->trade_type);

        if ($request->filled('status'))
            $query->where('status', $request->status);

        if ($request->filled('strength'))
            $query->where('signal_strength', $request->strength);

        $signals = $query->paginate(25)->withQueryString();

        // Summary stats for the header strip
        $stats = [
            'total'    => Signal::count(),
            'active'   => Signal::where('status', 'active')->count(),
            'tp_hit'   => Signal::whereIn('status', ['tp1_hit','tp2_hit','tp3_hit'])->count(),
            'sl_hit'   => Signal::where('status', 'sl_hit')->count(),
            'avg_conf' => Signal::avg('confidence'),
            'avg_pnl'  => Signal::whereNotNull('pnl_percent')->avg('pnl_percent'),
        ];

        $intervals = ['1m','5m','15m','1h','4h','1d','1w'];
        $statuses  = ['active','tp1_hit','tp2_hit','tp3_hit','sl_hit','expired'];
        $strengths = ['strong','medium','weak'];

        // Distinct symbols for the filter dropdown
        $symbols = Signal::distinct()->orderBy('symbol')->pluck('symbol');

        return view('signals.index', compact('signals','stats','intervals','statuses','strengths','symbols'));
    }

    /**
     * GET /signals/{signal}
     */
    public function show(Signal $signal): View
    {
        return view('signals.show', compact('signal'));
    }

    /**
     * POST /api/signals/generate
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'symbol'   => 'required|string|max:20',
            'interval' => 'required|in:1m,5m,15m,1h,4h,1d,1w',
        ]);

        try {
            $signal = $this->signals->generate(
                strtoupper($request->symbol),
                $request->interval
            );

            $entry = $signal['entry_price'];
            $sl    = $signal['stop_loss'];
            $tp1   = $signal['take_profit_1'];
            $risk  = abs($entry - $sl);

            $signal['risk_reward']  = $risk > 0 ? round(abs($tp1 - $entry) / $risk, 2) : 0;
            $signal['sl_percent']   = $entry > 0 ? round(abs($entry - $sl)  / $entry * 100, 2) : 0;
            $signal['tp1_percent']  = $entry > 0 ? round(abs($tp1 - $entry) / $entry * 100, 2) : 0;

            return response()->json(['success' => true, 'signal' => $signal]);

        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Signal generation failed: ' . $e->getMessage()], 502);
        }
    }

    /**
     * GET /api/signals/history
     */
    public function history(Request $request): JsonResponse
    {
        $query = Signal::orderByDesc('created_at');

        if ($request->filled('symbol'))     $query->where('symbol',     strtoupper($request->symbol));
        if ($request->filled('interval'))   $query->where('interval',   $request->interval);
        if ($request->filled('trade_type')) $query->where('trade_type', $request->trade_type);
        if ($request->filled('status'))     $query->where('status',     $request->status);

        $signals = $query->limit((int) $request->input('limit', 20))->get()
            ->map(function ($s) {
                $entry = (float) $s->entry_price;
                $sl    = (float) $s->stop_loss;
                $tp1   = (float) $s->take_profit_1;
                $risk  = abs($entry - $sl);

                $s->risk_reward = $risk > 0 ? round(abs($tp1 - $entry) / $risk, 2) : 0;
                $s->sl_percent  = $entry > 0 ? round(abs($entry - $sl)  / $entry * 100, 2) : 0;
                $s->tp1_percent = $entry > 0 ? round(abs($tp1 - $entry) / $entry * 100, 2) : 0;
                return $s;
            });

        return response()->json(['count' => $signals->count(), 'signals' => $signals]);
    }

    /**
     * POST /api/signals/{signal}/close
     */
    public function close(Signal $signal): JsonResponse
    {
        if ($signal->status !== 'active') {
            return response()->json(['error' => 'Signal is not active.'], 422);
        }

        try {
            $price = $this->binance->getCurrentPrice($signal->symbol);
            $entry = (float) $signal->entry_price;
            $pnl   = $signal->trade_type === 'long'
                ? ($price - $entry) / $entry * 100 * $signal->leverage
                : ($entry - $price) / $entry * 100 * $signal->leverage;

            $signal->update([
                'status'      => 'expired',
                'close_price' => $price,
                'pnl_percent' => round($pnl, 4),
                'closed_at'   => now(),
            ]);

            return response()->json(['success' => true, 'close_price' => $price, 'pnl_percent' => round($pnl, 4)]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
    }
}
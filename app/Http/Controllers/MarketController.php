<?php

namespace App\Http\Controllers;

use App\Models\Coin;
use App\Services\BinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    private BinanceService $binance;

    public function __construct(BinanceService $binance)
    {
        $this->binance = $binance;
    }

    /**
     * GET /api/market/price/{symbol}
     * Live 24h ticker for one symbol.
     */
    public function price(string $symbol): JsonResponse
    {
        try {
            $data = $this->binance->getTicker(strtoupper($symbol));
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
    }

    /**
     * GET /api/market/candles/{symbol}?interval=15m&limit=100
     * OHLCV candles for chart and indicators.
     */
    public function candles(Request $request, string $symbol): JsonResponse
    {
        $request->validate([
            'interval' => 'sometimes|in:1m,5m,15m,1h,4h,1d,1w',
            'limit'    => 'sometimes|integer|min:10|max:1000',
        ]);

        try {
            $candles = $this->binance->getCandles(
                strtoupper($symbol),
                $request->input('interval', '15m'),
                (int) $request->input('limit', 100),
                true
            );

            return response()->json($candles);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
    }

    /**
     * GET /api/market/orderbook/{symbol}?limit=10
     * Bids and asks with spread info.
     */
    public function orderBook(Request $request, string $symbol): JsonResponse
    {
        try {
            $data = $this->binance->getOrderBook(
                strtoupper($symbol),
                (int) $request->input('limit', 10)
            );
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
    }

    /**
     * GET /api/market/coins
     * All active coins with latest cached prices.
     */
    public function coins(): JsonResponse
    {
        $coins = Coin::active()
            ->orderBy('symbol')
            ->get(['symbol', 'base_asset', 'quote_asset', 'name',
                   'last_price', 'price_change_24h', 'high_24h',
                   'low_24h', 'volume_24h', 'last_updated']);

        return response()->json($coins);
    }

    /**
     * POST /api/market/refresh-all
     * Refresh prices for every active coin in the DB.
     */
    public function refreshAll(): JsonResponse
    {
        try {
            $count = $this->binance->refreshAllCoinPrices();
            return response()->json(['updated' => $count, 'message' => "Refreshed {$count} coins"]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
    }
}
<?php

namespace App\Services;

use App\Models\Candle;
use App\Models\Coin;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class BinanceService
{
    protected string $baseUrl = 'https://fapi.binance.com/fapi/v1';  // Binance Futures (USDT-Margined)

    // ─────────────────────────────────────────────────────────────────────
    //  TICKER — 24h price stats
    // ─────────────────────────────────────────────────────────────────────

    public function getTicker(string $symbol): array
    {
        $symbol = strtoupper($symbol);
        $ticker = $this->get('/ticker/24hr', ['symbol' => $symbol]);
        $book   = $this->get('/depth',       ['symbol' => $symbol, 'limit' => 5]);

        $price = (float) $ticker['lastPrice'];
        $bid   = (float) ($book['bids'][0][0] ?? 0);
        $ask   = (float) ($book['asks'][0][0] ?? 0);

        $result = [
            'symbol'         => $symbol,
            'price'          => $price,
            'change_percent' => (float) $ticker['priceChangePercent'],
            'change_abs'     => (float) $ticker['priceChange'],
            'high'           => (float) $ticker['highPrice'],
            'low'            => (float) $ticker['lowPrice'],
            'volume'         => (float) $ticker['volume'],
            'quote_volume'   => (float) $ticker['quoteVolume'],
            'bid'            => $bid,
            'ask'            => $ask,
            'spread_percent' => $bid > 0 ? round(($ask - $bid) / $bid * 100, 6) : 0,
            'num_trades'     => (int) $ticker['count'],
        ];

        $this->updateCoinPrice($symbol, $result);
        return $result;
    }

    public function getMultipleTickers(array $symbols): array
    {
        // Futures API does not support ?symbols=[...] batch param like Spot.
        // Call without symbol to get all tickers, then filter.
        $tickers = $this->get('/ticker/24hr');

        $symbolSet = array_flip(array_map('strtoupper', $symbols));

        return collect($tickers)
            ->filter(fn($t) => isset($symbolSet[$t['symbol']]))
            ->map(fn($t) => [
                'symbol'         => $t['symbol'],
                'price'          => (float) $t['lastPrice'],
                'change_percent' => (float) $t['priceChangePercent'],
                'high'           => (float) $t['highPrice'],
                'low'            => (float) $t['lowPrice'],
                'quote_volume'   => (float) $t['quoteVolume'],
            ])
            ->keyBy('symbol')
            ->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────
    //  ALL BINANCE USDT PAIRS — sync to coins table
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Fetch every active USDT trading pair from Binance exchange info
     * and upsert into the coins table.
     * Returns the count of coins synced.
     */
    public function syncAllCoins(): int
    {
        // Use Futures exchangeInfo to get only PERPETUAL USDT contracts
        $info    = $this->get('/exchangeInfo');
        $symbols = $info['symbols'] ?? [];
        $count   = 0;

        foreach ($symbols as $s) {
            if (
                $s['status']       !== 'TRADING'   ||
                $s['quoteAsset']   !== 'USDT'       ||
                ($s['contractType'] ?? '') !== 'PERPETUAL'
            ) continue;

            Coin::updateOrCreate(
                ['symbol' => $s['symbol']],
                [
                    'base_asset'  => $s['baseAsset'],
                    'quote_asset' => 'USDT',
                    'name'        => $s['baseAsset'],
                    'is_active'   => true,
                ]
            );
            $count++;
        }

        return $count;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  CANDLES
    // ─────────────────────────────────────────────────────────────────────

    public function getCandles(
        string $symbol,
        string $interval = '15m',
        int    $limit    = 100,
        bool   $persist  = true
    ): array {
        $symbol = strtoupper($symbol);
        $raw    = $this->get('/klines', [
            'symbol'   => $symbol,
            'interval' => $interval,
            'limit'    => $limit,
        ]);

        $candles = array_map(fn($c) => [
            'symbol'       => $symbol,
            'interval'     => $interval,
            'open_time'    => (int)   $c[0],
            'open'         => (float) $c[1],
            'high'         => (float) $c[2],
            'low'          => (float) $c[3],
            'close'        => (float) $c[4],
            'volume'       => (float) $c[5],
            'close_time'   => (int)   $c[6],
            'quote_volume' => (float) $c[7],
            'num_trades'   => (int)   $c[8],
        ], $raw);

        if ($persist) $this->persistCandles($candles);
        return $candles;
    }

    public function getClosePrices(string $symbol, string $interval = '15m', int $limit = 200): array
    {
        return array_column($this->getCandles($symbol, $interval, $limit, false), 'close');
    }

    public function getClosePricesCached(string $symbol, string $interval = '15m', int $limit = 200): array
    {
        $symbol  = strtoupper($symbol);
        $dbCount = Candle::where('symbol', $symbol)->where('interval', $interval)->count();

        if ($dbCount >= $limit) {
            $closes = Candle::where('symbol', $symbol)
                ->where('interval', $interval)
                ->orderBy('open_time', 'desc')
                ->limit($limit)
                ->pluck('close')
                ->toArray();
            return array_reverse($closes);
        }

        return $this->getClosePrices($symbol, $interval, $limit);
    }

    public function getOhlcv(string $symbol, string $interval = '15m', int $limit = 100): array
    {
        return $this->getCandles($symbol, $interval, $limit, true);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  ORDER BOOK
    // ─────────────────────────────────────────────────────────────────────

    public function getOrderBook(string $symbol, int $limit = 10): array
    {
        $symbol = strtoupper($symbol);
        $book   = $this->get('/depth', ['symbol' => $symbol, 'limit' => $limit]);

        $bids    = array_map(fn($b) => [(float) $b[0], (float) $b[1]], $book['bids']);
        $asks    = array_map(fn($a) => [(float) $a[0], (float) $a[1]], $book['asks']);
        $bestBid = $bids[0][0] ?? 0;
        $bestAsk = $asks[0][0] ?? 0;
        $spread  = $bestAsk - $bestBid;

        return [
            'symbol'         => $symbol,
            'bids'           => $bids,
            'asks'           => $asks,
            'best_bid'       => $bestBid,
            'best_ask'       => $bestAsk,
            'mid_price'      => ($bestBid + $bestAsk) / 2,
            'spread'         => $spread,
            'spread_percent' => $bestBid > 0 ? round($spread / $bestBid * 100, 6) : 0,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    //  CURRENT PRICE
    // ─────────────────────────────────────────────────────────────────────

    public function getCurrentPrice(string $symbol): float
    {
        // Futures /fapi/v1/ticker/price returns { symbol, price, time }
        $data = $this->get('/ticker/price', ['symbol' => strtoupper($symbol)]);
        return (float) ($data['price'] ?? $data['lastPrice'] ?? 0);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  REFRESH ALL COIN PRICES
    // ─────────────────────────────────────────────────────────────────────

    public function refreshAllCoinPrices(): int
    {
        $symbols = Coin::active()->pluck('symbol')->toArray();
        if (empty($symbols)) return 0;

        // Binance allows max 100 symbols per batch call
        $chunks  = array_chunk($symbols, 100);
        $updated = 0;

        foreach ($chunks as $chunk) {
            $tickers = $this->getMultipleTickers($chunk);
            foreach ($tickers as $symbol => $data) {
                Coin::where('symbol', $symbol)->update([
                    'last_price'       => $data['price'],
                    'price_change_24h' => $data['change_percent'],
                    'high_24h'         => $data['high'],
                    'low_24h'          => $data['low'],
                    'volume_24h'       => $data['quote_volume'],
                    'last_updated'     => now(),
                ]);
                $updated++;
            }
        }
        return $updated;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────

    private function get(string $endpoint, array $params = []): array
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl . $endpoint, $params);

            if ($response->failed()) {
                $error = $response->json('msg') ?? $response->body();
                throw new \RuntimeException("Binance API error [{$endpoint}]: {$error}");
            }
            return $response->json();
        } catch (RequestException $e) {
            Log::error('BinanceService HTTP error', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            throw new \RuntimeException("Binance request failed: " . $e->getMessage());
        }
    }

    private function persistCandles(array $candles): void
    {
        foreach ($candles as $candle) {
            Candle::updateOrCreate(
                ['symbol' => $candle['symbol'], 'interval' => $candle['interval'], 'open_time' => $candle['open_time']],
                $candle
            );
        }
    }

    private function updateCoinPrice(string $symbol, array $data): void
    {
        Coin::where('symbol', $symbol)->update([
            'last_price'       => $data['price'],
            'price_change_24h' => $data['change_percent'],
            'high_24h'         => $data['high'],
            'low_24h'          => $data['low'],
            'volume_24h'       => $data['quote_volume'],
            'last_updated'     => now(),
        ]);
    }
}

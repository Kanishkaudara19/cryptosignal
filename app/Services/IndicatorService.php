<?php

namespace App\Services;

use App\Models\IndicatorCache;
use Illuminate\Support\Facades\Log;

class IndicatorService
{
    private BinanceService $binance;

    public function __construct(BinanceService $binance)
    {
        $this->binance = $binance;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  MAIN ENTRY — calculate all indicators and cache them
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Calculate ALL indicators for a symbol/interval, cache in DB, return result.
     *
     * @param string $symbol   e.g. BTCUSDT
     * @param string $interval e.g. 15m
     * @param bool   $force    Skip cache and recalculate
     */
    public function calculate(string $symbol, string $interval = '15m', bool $force = false): array
    {
        $symbol = strtoupper($symbol);

        // Return cached result if fresh (within 1 candle period) and not forced
        if (!$force) {
            $cached = $this->getCached($symbol, $interval);
            if ($cached) return $cached;
        }

        // Fetch enough candles for all indicators (EMA50 needs 50+, MACD needs 35+)
        $closes = $this->binance->getClosePricesCached($symbol, $interval, 200);
        $ohlcv  = $this->binance->getOhlcv($symbol, $interval, 100);

        if (count($closes) < 60) {
            throw new \RuntimeException("Not enough candle data for {$symbol} ({$interval}). Got " . count($closes) . ", need 60+.");
        }

        // ── Calculate each indicator ───────────────────────────────────
        $rsiValue    = $this->rsi($closes, 14);
        $macdResult  = $this->macd($closes, 12, 26, 9);
        $ema9Value   = $this->ema($closes, 9);
        $ema21Value  = $this->ema($closes, 21);
        $ema50Value  = $this->ema($closes, 50);
        $bbResult    = $this->bollingerBands($closes, 20, 2.0);
        $atrValue    = $this->atr($ohlcv, 14);

        $currentPrice = end($closes);

        // ── Derive signals from each indicator ─────────────────────────
        $rsiSignal   = $this->interpretRsi($rsiValue);
        $macdCross   = $this->interpretMacd($macdResult);
        $emaTrend    = $this->interpretEma($currentPrice, $ema9Value, $ema21Value, $ema50Value);
        $bbPosition  = $this->interpretBB($currentPrice, $bbResult);

        // ── Overall trend confluence ───────────────────────────────────
        $overall = $this->overallTrend($rsiSignal, $macdCross, $emaTrend, $bbPosition);

        $result = [
            'symbol'           => $symbol,
            'interval'         => $interval,

            // RSI
            'rsi'              => round($rsiValue, 4),
            'rsi_signal'       => $rsiSignal,

            // MACD
            'macd'             => round($macdResult['macd'], 8),
            'macd_signal_line' => round($macdResult['signal'], 8),
            'macd_histogram'   => round($macdResult['histogram'], 8),
            'macd_cross'       => $macdCross,

            // EMA
            'ema9'             => round($ema9Value, 8),
            'ema21'            => round($ema21Value, 8),
            'ema50'            => round($ema50Value, 8),
            'ema_trend'        => $emaTrend,

            // Bollinger Bands
            'bb_upper'         => round($bbResult['upper'], 8),
            'bb_middle'        => round($bbResult['middle'], 8),
            'bb_lower'         => round($bbResult['lower'], 8),
            'bb_bandwidth'     => round($bbResult['bandwidth'], 6),
            'bb_position'      => $bbPosition,

            // ATR (used by SignalService for TP/SL, not cached separately)
            'atr'              => round($atrValue, 8),

            // Overall
            'overall_trend'    => $overall['trend'],
            'trend_strength'   => round($overall['strength'], 2),

            'calculated_at'    => now()->toDateTimeString(),
        ];

        // Persist to indicator_caches table
        $this->persist($result);

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  RSI — Relative Strength Index
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Calculate RSI using Wilder's smoothing method.
     *
     * @param float[] $closes  Array of closing prices (oldest → newest)
     * @param int     $period  Default 14
     * @return float  RSI value 0–100
     */
    public function rsi(array $closes, int $period = 14): float
    {
        $closes = array_values($closes);
        $count  = count($closes);

        if ($count < $period + 1) {
            throw new \InvalidArgumentException("RSI needs at least " . ($period + 1) . " closes, got {$count}");
        }

        // Step 1: Calculate first $period changes
        $gains  = [];
        $losses = [];

        for ($i = 1; $i <= $period; $i++) {
            $change = $closes[$i] - $closes[$i - 1];
            $gains[]  = max($change, 0);
            $losses[] = max(-$change, 0);
        }

        // Step 2: Initial averages (simple average for first period)
        $avgGain = array_sum($gains)  / $period;
        $avgLoss = array_sum($losses) / $period;

        // Step 3: Wilder's smoothing for remaining candles
        for ($i = $period + 1; $i < $count; $i++) {
            $change  = $closes[$i] - $closes[$i - 1];
            $gain    = max($change, 0);
            $loss    = max(-$change, 0);

            $avgGain = ($avgGain * ($period - 1) + $gain)  / $period;
            $avgLoss = ($avgLoss * ($period - 1) + $loss)  / $period;
        }

        if ($avgLoss == 0) return 100.0;

        $rs  = $avgGain / $avgLoss;
        return 100 - (100 / (1 + $rs));
    }

    // ─────────────────────────────────────────────────────────────────────
    //  MACD — Moving Average Convergence Divergence
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Calculate MACD line, signal line, and histogram.
     *
     * Standard settings: fast=12, slow=26, signal=9
     *
     * @param float[] $closes
     * @return array  ['macd' => float, 'signal' => float, 'histogram' => float]
     */
    public function macd(array $closes, int $fast = 12, int $slow = 26, int $signal = 9): array
    {
        $closes = array_values($closes);

        if (count($closes) < $slow + $signal) {
            throw new \InvalidArgumentException("MACD needs at least " . ($slow + $signal) . " closes.");
        }

        // Build EMA arrays for all data points
        $emaFastArr = $this->emaArray($closes, $fast);
        $emaSlowArr = $this->emaArray($closes, $slow);

        // Align arrays (slow EMA starts later)
        $offset    = $slow - $fast;
        $macdLine  = [];

        for ($i = 0; $i < count($emaSlowArr); $i++) {
            $macdLine[] = $emaFastArr[$i + $offset] - $emaSlowArr[$i];
        }

        // Signal line = EMA of MACD line
        $signalArr  = $this->emaArray($macdLine, $signal);

        // Align signal to macd line
        $macdAligned   = array_slice($macdLine, count($macdLine) - count($signalArr));
        $lastMacd      = end($macdAligned);
        $lastSignal    = end($signalArr);

        return [
            'macd'      => $lastMacd,
            'signal'    => $lastSignal,
            'histogram' => $lastMacd - $lastSignal,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    //  EMA — Exponential Moving Average (latest value)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Calculate the latest EMA value.
     *
     * @param float[] $closes
     * @param int     $period
     * @return float  Latest EMA value
     */
    public function ema(array $closes, int $period): float
    {
        $arr = $this->emaArray(array_values($closes), $period);
        return end($arr);
    }

    /**
     * Calculate a full array of EMA values (one per candle after warmup).
     *
     * @param float[] $data
     * @param int     $period
     * @return float[]
     */
    public function emaArray(array $data, int $period): array
    {
        $data  = array_values($data);
        $count = count($data);

        if ($count < $period) {
            throw new \InvalidArgumentException("EMA({$period}) needs at least {$period} data points, got {$count}.");
        }

        $k      = 2 / ($period + 1);  // smoothing multiplier
        $result = [];

        // Seed with simple moving average of first $period values
        $seed = array_sum(array_slice($data, 0, $period)) / $period;
        $result[] = $seed;

        for ($i = $period; $i < $count; $i++) {
            $result[] = ($data[$i] - end($result)) * $k + end($result);
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  BOLLINGER BANDS
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Calculate Bollinger Bands.
     *
     * Upper = SMA(20) + 2 * StdDev
     * Lower = SMA(20) - 2 * StdDev
     *
     * @param float[] $closes
     * @param int     $period          Default 20
     * @param float   $stdDevMultiplier Default 2.0
     * @return array  ['upper', 'middle', 'lower', 'bandwidth', 'percent_b']
     */
    public function bollingerBands(array $closes, int $period = 20, float $stdDevMultiplier = 2.0): array
    {
        $closes = array_values($closes);
        $count  = count($closes);

        if ($count < $period) {
            throw new \InvalidArgumentException("Bollinger Bands need at least {$period} closes.");
        }

        // Use the last $period closes
        $window = array_slice($closes, -$period);
        $sma    = array_sum($window) / $period;

        // Population standard deviation
        $variance = array_sum(array_map(fn($v) => ($v - $sma) ** 2, $window)) / $period;
        $stdDev   = sqrt($variance);

        $upper = $sma + ($stdDevMultiplier * $stdDev);
        $lower = $sma - ($stdDevMultiplier * $stdDev);

        // Bandwidth = (Upper - Lower) / Middle * 100
        $bandwidth = $sma > 0 ? ($upper - $lower) / $sma * 100 : 0;

        // %B = (Price - Lower) / (Upper - Lower)
        $currentPrice = end($closes);
        $percentB     = ($upper - $lower) > 0 ? ($currentPrice - $lower) / ($upper - $lower) : 0.5;

        return [
            'upper'     => $upper,
            'middle'    => $sma,
            'lower'     => $lower,
            'bandwidth' => $bandwidth,
            'percent_b' => $percentB,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    //  ATR — Average True Range (volatility measure for TP/SL)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Calculate ATR using Wilder's smoothing.
     * Used by SignalService to set dynamic TP and SL distances.
     *
     * @param array $ohlcv  Array of ['high','low','close'] candles
     * @param int   $period Default 14
     * @return float ATR value
     */
    public function atr(array $ohlcv, int $period = 14): float
    {
        if (count($ohlcv) < $period + 1) {
            throw new \InvalidArgumentException("ATR needs at least " . ($period + 1) . " candles.");
        }

        $trValues = [];

        for ($i = 1; $i < count($ohlcv); $i++) {
            $high     = (float) $ohlcv[$i]['high'];
            $low      = (float) $ohlcv[$i]['low'];
            $prevClose = (float) $ohlcv[$i - 1]['close'];

            // True Range = max of:
            //   High - Low
            //   |High - Previous Close|
            //   |Low  - Previous Close|
            $trValues[] = max(
                $high - $low,
                abs($high - $prevClose),
                abs($low  - $prevClose)
            );
        }

        // Initial ATR = simple average of first $period TRs
        $atr = array_sum(array_slice($trValues, 0, $period)) / $period;

        // Wilder's smoothing for remaining values
        for ($i = $period; $i < count($trValues); $i++) {
            $atr = ($atr * ($period - 1) + $trValues[$i]) / $period;
        }

        return $atr;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  SMA — Simple Moving Average (helper)
    // ─────────────────────────────────────────────────────────────────────

    public function sma(array $closes, int $period): float
    {
        $closes = array_values($closes);
        $window = array_slice($closes, -$period);

        if (count($window) < $period) {
            throw new \InvalidArgumentException("SMA({$period}) needs {$period} values.");
        }

        return array_sum($window) / $period;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  INTERPRETERS — derive human-readable signals from raw values
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Interpret RSI value into a signal string.
     */
    private function interpretRsi(float $rsi): string
    {
        if ($rsi >= 70) return 'overbought';
        if ($rsi <= 30) return 'oversold';
        if ($rsi >= 55) return 'bullish';
        if ($rsi <= 45) return 'bearish';
        return 'neutral';
    }

    /**
     * Detect MACD crossover direction.
     */
    private function interpretMacd(array $macd): string
    {
        $histogram = $macd['histogram'];
        $macdLine  = $macd['macd'];

        if ($histogram > 0 && $macdLine > 0) return 'bullish_cross';
        if ($histogram < 0 && $macdLine < 0) return 'bearish_cross';
        if ($histogram > 0)                  return 'bullish';
        if ($histogram < 0)                  return 'bearish';
        return 'none';
    }

    /**
     * Interpret EMA alignment into trend direction.
     *
     * Bullish:  price > ema9 > ema21 > ema50
     * Bearish:  price < ema9 < ema21 < ema50
     */
    private function interpretEma(float $price, float $ema9, float $ema21, float $ema50): string
    {
        if ($price > $ema9 && $ema9 > $ema21 && $ema21 > $ema50) return 'bullish';
        if ($price < $ema9 && $ema9 < $ema21 && $ema21 < $ema50) return 'bearish';
        if ($price > $ema21 && $price > $ema50)                   return 'bullish';
        if ($price < $ema21 && $price < $ema50)                   return 'bearish';
        return 'neutral';
    }

    /**
     * Interpret where price sits within the Bollinger Bands.
     */
    private function interpretBB(float $price, array $bb): string
    {
        if ($price > $bb['upper']) return 'above_upper';   // overbought zone
        if ($price < $bb['lower']) return 'below_lower';   // oversold zone
        if ($price > $bb['middle']) return 'upper_half';   // bullish lean
        if ($price < $bb['middle']) return 'lower_half';   // bearish lean
        return 'at_middle';
    }

    // ─────────────────────────────────────────────────────────────────────
    //  OVERALL TREND CONFLUENCE
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Score all indicators and determine overall trend + strength.
     *
     * Scoring (each indicator contributes):
     *   RSI:  oversold=+2, bullish=+1, bearish=-1, overbought=-2
     *   MACD: bullish_cross=+2, bullish=+1, bearish=-1, bearish_cross=-2
     *   EMA:  bullish=+2, bearish=-2, neutral=0
     *   BB:   below_lower=+1 (reversal), above_upper=-1, upper_half=+1, lower_half=-1
     *
     * Max score: +7 (strong bullish), Min score: -7 (strong bearish)
     */
    private function overallTrend(
        string $rsiSignal,
        string $macdCross,
        string $emaTrend,
        string $bbPosition
    ): array {
        $score = 0;

        // RSI score
        switch ($rsiSignal) {
            case 'oversold':   $score += 2;  break;
            case 'bullish':    $score += 1;  break;
            case 'bearish':    $score -= 1;  break;
            case 'overbought': $score -= 2;  break;
        }

        // MACD score
        switch ($macdCross) {
            case 'bullish_cross': $score += 2; break;
            case 'bullish':       $score += 1; break;
            case 'bearish':       $score -= 1; break;
            case 'bearish_cross': $score -= 2; break;
        }

        // EMA score
        switch ($emaTrend) {
            case 'bullish': $score += 2; break;
            case 'bearish': $score -= 2; break;
        }

        // Bollinger Bands score
        switch ($bbPosition) {
            case 'below_lower': $score += 1; break;  // price at support — potential bounce
            case 'upper_half':  $score += 1; break;
            case 'lower_half':  $score -= 1; break;
            case 'above_upper': $score -= 1; break;  // price extended — potential reversal
        }

        // Convert score (-7 to +7) to trend + strength %
        $maxScore = 7;
        $strength = min(abs($score) / $maxScore * 100, 100);

        if ($score >= 3) {
            $trend = 'bullish';
        } elseif ($score <= -3) {
            $trend = 'bearish';
        } else {
            $trend = 'neutral';
        }

        return ['trend' => $trend, 'strength' => $strength, 'score' => $score];
    }

    // ─────────────────────────────────────────────────────────────────────
    //  CACHE HELPERS
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Return cached indicators if they exist and are recent enough.
     */
    private function getCached(string $symbol, string $interval): ?array
    {
        $cache = IndicatorCache::where('symbol', $symbol)
            ->where('interval', $interval)
            ->first();

        if (!$cache) return null;

        // Consider cache stale after 1 interval period
        $staleCutoff = $this->staleCutoff($interval);
        if ($cache->calculated_at < $staleCutoff) return null;

        return $cache->toArray();
    }

    /**
     * Upsert indicator results into indicator_caches table.
     */
    private function persist(array $data): void
    {
        IndicatorCache::updateOrCreate(
            ['symbol' => $data['symbol'], 'interval' => $data['interval']],
            array_merge($data, ['calculated_at' => now()])
        );
    }

    /**
     * Get the timestamp before which a cached result is considered stale.
     */
    private function staleCutoff(string $interval): \Carbon\Carbon
    {
        switch ($interval) {
            case '1m':  $minutes = 1;     break;
            case '5m':  $minutes = 5;     break;
            case '15m': $minutes = 15;    break;
            case '1h':  $minutes = 60;    break;
            case '4h':  $minutes = 240;   break;
            case '1d':  $minutes = 1440;  break;
            case '1w':  $minutes = 10080; break;
            default:    $minutes = 15;    break;
        }

        return now()->subMinutes($minutes);
    }
}
<?php

namespace App\Services;

use App\Models\Coin;
use App\Models\Signal;
use Illuminate\Support\Facades\Log;

class SignalService
{
    // ── Scoring thresholds ─────────────────────────────────────────────
    private const STRONG_SIGNAL_SCORE = 5;
    private const MEDIUM_SIGNAL_SCORE = 3;
    private const MIN_SIGNAL_SCORE    = 2;

    private BinanceService   $binance;
    private IndicatorService $indicators;

    public function __construct(BinanceService $binance, IndicatorService $indicators)
    {
        $this->binance     = $binance;
        $this->indicators  = $indicators;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  MAIN ENTRY
    // ─────────────────────────────────────────────────────────────────────

    public function generate(string $symbol, string $interval = '15m'): array
    {
        $symbol = strtoupper($symbol);

        $ind          = $this->indicators->calculate($symbol, $interval, true);
        $ohlcv        = $this->binance->getOhlcv($symbol, $interval, 100);
        $currentPrice = $this->binance->getCurrentPrice($symbol);

        $longScore  = $this->scoreLong($ind);
        $shortScore = $this->scoreShort($ind);
        $direction  = $this->decideDirection($longScore, $shortScore);

        if ($direction === 'none') {
            throw new \RuntimeException(
                "No clear signal for {$symbol} on {$interval}. " .
                "Long: {$longScore}, Short: {$shortScore}. Market is neutral."
            );
        }

        $atr        = $this->indicators->atr($ohlcv, 14);
        $entryPrice = $this->calcEntryPrice($currentPrice, $direction, $atr);

        // ── Part 5: refined targets with S/R ──────────────────────────
        $levels  = $this->calcSupportResistance($ohlcv);
        $targets = $this->calcTargets($entryPrice, $direction, $atr, $ind, $levels);

        $score      = $direction === 'long' ? $longScore : $shortScore;
        $confidence = $this->calcConfidence($score, $ind);
        $strength   = $this->calcStrength($score);
        $leverage   = $this->suggestLeverage($atr, $currentPrice, $strength);
        $mode       = $this->suggestMode($strength, $leverage, $confidence);
        $coinName   = Coin::where('symbol', $symbol)->value('name') ?? $symbol;

        $signal = [
            'symbol'          => $symbol,
            'coin_name'       => $coinName,
            'interval'        => $interval,
            'trade_type'      => $direction,
            'mode'            => $mode,
            'entry_price'     => round($entryPrice, 8),
            'leverage'        => $leverage,
            'take_profit_1'   => round($targets['tp1'], 8),
            'take_profit_2'   => round($targets['tp2'], 8),
            'take_profit_3'   => round($targets['tp3'], 8),
            'stop_loss'       => round($targets['sl'], 8),
            'confidence'      => round($confidence, 2),
            'signal_strength' => $strength,
            'rsi'             => $ind['rsi'],
            'macd'            => $ind['macd'],
            'macd_signal'     => $ind['macd_signal_line'],
            'ema9'            => $ind['ema9'],
            'ema21'           => $ind['ema21'],
            'ema50'           => $ind['ema50'],
            'bb_upper'        => $ind['bb_upper'],
            'bb_middle'       => $ind['bb_middle'],
            'bb_lower'        => $ind['bb_lower'],
        ];

        $saved          = Signal::create(array_merge($signal, ['status' => 'active']));
        $signal['id']   = $saved->id;

        Log::info("Signal generated: {$symbol} {$direction} @ {$entryPrice} [{$interval}]", [
            'confidence' => $confidence,
            'strength'   => $strength,
            'leverage'   => $leverage,
        ]);

        return $signal;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  SUPPORT & RESISTANCE DETECTION
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Identify key support and resistance levels from OHLCV data.
     *
     * Method: pivot point analysis
     *   A pivot HIGH = candle whose high is higher than N neighbours on each side
     *   A pivot LOW  = candle whose low  is lower  than N neighbours on each side
     *
     * Returns the nearest support levels below price
     * and nearest resistance levels above price.
     */
    public function calcSupportResistance(array $ohlcv, int $pivotStrength = 3): array
    {
        $highs      = array_column($ohlcv, 'high');
        $lows       = array_column($ohlcv, 'low');
        $count      = count($ohlcv);
        $currentPrice = (float) end($ohlcv)['close'];

        $resistanceLevels = [];
        $supportLevels    = [];

        for ($i = $pivotStrength; $i < $count - $pivotStrength; $i++) {

            // Pivot high — resistance
            $isPivotHigh = true;
            for ($j = 1; $j <= $pivotStrength; $j++) {
                if ($highs[$i] <= $highs[$i - $j] || $highs[$i] <= $highs[$i + $j]) {
                    $isPivotHigh = false;
                    break;
                }
            }
            if ($isPivotHigh && $highs[$i] > $currentPrice) {
                $resistanceLevels[] = (float) $highs[$i];
            }

            // Pivot low — support
            $isPivotLow = true;
            for ($j = 1; $j <= $pivotStrength; $j++) {
                if ($lows[$i] >= $lows[$i - $j] || $lows[$i] >= $lows[$i + $j]) {
                    $isPivotLow = false;
                    break;
                }
            }
            if ($isPivotLow && $lows[$i] < $currentPrice) {
                $supportLevels[] = (float) $lows[$i];
            }
        }

        // Sort: support descending (nearest first), resistance ascending (nearest first)
        rsort($supportLevels);
        sort($resistanceLevels);

        // Cluster nearby levels (within 0.3% of each other → keep strongest)
        $supportLevels    = $this->clusterLevels($supportLevels, $currentPrice, 0.003);
        $resistanceLevels = $this->clusterLevels($resistanceLevels, $currentPrice, 0.003);

        return [
            'support'    => array_slice($supportLevels, 0, 3),     // 3 nearest supports
            'resistance' => array_slice($resistanceLevels, 0, 3),  // 3 nearest resistances
        ];
    }

    /**
     * Merge levels that are within $threshold % of each other.
     */
    private function clusterLevels(array $levels, float $price, float $threshold = 0.003): array
    {
        if (empty($levels)) return [];

        $clustered = [];
        $used      = array_fill(0, count($levels), false);

        foreach ($levels as $i => $level) {
            if ($used[$i]) continue;
            $group = [$level];
            foreach ($levels as $j => $other) {
                if ($i === $j || $used[$j]) continue;
                if (abs($level - $other) / max($price, 0.0001) < $threshold) {
                    $group[] = $other;
                    $used[$j] = true;
                }
            }
            $clustered[] = array_sum($group) / count($group); // average of cluster
            $used[$i] = true;
        }

        return $clustered;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  REFINED TP / SL CALCULATION
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Calculate TP1, TP2, TP3, and Stop Loss.
     *
     * Strategy:
     *   1. Start with ATR-based raw targets
     *   2. Snap each TP to the nearest S/R level if one falls within ±15% of the ATR target
     *   3. Snap SL to just beyond the nearest support (long) or resistance (short)
     *   4. Guarantee minimum risk/reward of 1.2 on TP1
     */
    private function calcTargets(
        float  $entry,
        string $direction,
        float  $atr,
        array  $ind,
        array  $levels
    ): array {
        // Raw ATR targets
        $raw = $this->rawTargets($entry, $direction, $atr);

        // Try to snap TPs to S/R levels
        $tp1 = $this->snapToLevel($raw['tp1'], $direction === 'long' ? $levels['resistance'] : $levels['support'], $atr * 0.15, $direction, 'tp');
        $tp2 = $this->snapToLevel($raw['tp2'], $direction === 'long' ? $levels['resistance'] : $levels['support'], $atr * 0.15, $direction, 'tp');
        $tp3 = $this->snapToLevel($raw['tp3'], $direction === 'long' ? $levels['resistance'] : $levels['support'], $atr * 0.15, $direction, 'tp');

        // Try to snap SL to just beyond nearest S/R
        $sl  = $this->snapToLevel($raw['sl'],  $direction === 'long' ? $levels['support']    : $levels['resistance'], $atr * 0.2,  $direction, 'sl');

        // Ensure TPs are in the correct order
        [$tp1, $tp2, $tp3] = $this->orderTargets($tp1, $tp2, $tp3, $direction);

        // Guarantee minimum R:R of 1.2 on TP1
        $minTP1 = $this->enforceMinRR($entry, $sl, $direction, 1.2);
        if ($direction === 'long'  && $tp1 < $minTP1) $tp1 = $minTP1;
        if ($direction === 'short' && $tp1 > $minTP1) $tp1 = $minTP1;

        // Ensure TP2 > TP1 and TP3 > TP2 (long), reversed for short
        [$tp1, $tp2, $tp3] = $this->orderTargets($tp1, $tp2, $tp3, $direction);

        return compact('tp1', 'tp2', 'tp3', 'sl');
    }

    /**
     * Raw ATR-based targets (no S/R snapping).
     *
     * Long:   TP1=1.0×ATR, TP2=2.0×ATR, TP3=3.5×ATR above entry
     *         SL=0.8×ATR below entry
     * Short:  mirror image
     */
    private function rawTargets(float $entry, string $direction, float $atr): array
    {
        $mults = ['tp1' => 1.0, 'tp2' => 2.0, 'tp3' => 3.5, 'sl' => 0.8];

        if ($direction === 'long') {
            return [
                'tp1' => $entry + $atr * $mults['tp1'],
                'tp2' => $entry + $atr * $mults['tp2'],
                'tp3' => $entry + $atr * $mults['tp3'],
                'sl'  => $entry - $atr * $mults['sl'],
            ];
        }

        return [
            'tp1' => $entry - $atr * $mults['tp1'],
            'tp2' => $entry - $atr * $mults['tp2'],
            'tp3' => $entry - $atr * $mults['tp3'],
            'sl'  => $entry + $atr * $mults['sl'],
        ];
    }

    /**
     * Snap a raw target to the nearest S/R level if within tolerance.
     *
     * For TP levels: snap to S/R that is just beyond the raw target
     * For SL levels: snap to S/R that is just beyond entry (outside the trade)
     */
    private function snapToLevel(
        float  $rawTarget,
        array  $levels,
        float  $tolerance,
        string $direction,
        string $type
    ): float {
        if (empty($levels)) return $rawTarget;

        $best      = null;
        $bestDelta = PHP_FLOAT_MAX;

        foreach ($levels as $level) {
            $delta = abs($level - $rawTarget);
            if ($delta < $tolerance && $delta < $bestDelta) {
                // For TP: level must be in profit direction
                // For SL: level must be beyond the raw SL (more protection)
                $validTP = $type === 'tp' && (
                    ($direction === 'long'  && $level >= $rawTarget * 0.995) ||
                    ($direction === 'short' && $level <= $rawTarget * 1.005)
                );
                $validSL = $type === 'sl' && (
                    ($direction === 'long'  && $level <= $rawTarget * 1.005) ||
                    ($direction === 'short' && $level >= $rawTarget * 0.995)
                );
                if ($validTP || $validSL) {
                    $best      = $level;
                    $bestDelta = $delta;
                }
            }
        }

        return $best ?? $rawTarget;
    }

    /**
     * Ensure TP levels are ordered correctly (ascending for long, descending for short).
     */
    private function orderTargets(float $tp1, float $tp2, float $tp3, string $direction): array
    {
        $tps = [$tp1, $tp2, $tp3];

        if ($direction === 'long') {
            sort($tps);
        } else {
            rsort($tps);
        }

        return $tps;
    }

    /**
     * Calculate minimum TP1 price to guarantee a given risk/reward ratio.
     */
    private function enforceMinRR(float $entry, float $sl, string $direction, float $minRR): float
    {
        $risk = abs($entry - $sl);

        return $direction === 'long'
            ? $entry + ($risk * $minRR)
            : $entry - ($risk * $minRR);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  SCORING (same as Part 4)
    // ─────────────────────────────────────────────────────────────────────

    private function scoreLong(array $ind): int
    {
        $score = 0;
        $rsi   = (float) $ind['rsi'];

        if ($rsi <= 30)                          $score += 3;
        elseif ($rsi <= 40)                      $score += 2;
        elseif ($rsi >= 45 && $rsi <= 60)        $score += 1;

        if ($ind['macd_cross'] === 'bullish_cross')   $score += 2;
        elseif ($ind['macd_cross'] === 'bullish')     $score += 1;
        if ((float)$ind['macd_histogram'] > 0 && (float)$ind['macd'] > 0) $score += 1;

        if ($ind['ema_trend'] === 'bullish')          $score += 2;
        elseif ((float)$ind['ema9'] > (float)$ind['ema21']) $score += 1;

        if ($ind['bb_position'] === 'below_lower')    $score += 2;
        elseif ($ind['bb_position'] === 'lower_half') $score += 1;

        if ($ind['overall_trend'] === 'bullish')      $score += 1;

        return $score;
    }

    private function scoreShort(array $ind): int
    {
        $score = 0;
        $rsi   = (float) $ind['rsi'];

        if ($rsi >= 70)                          $score += 3;
        elseif ($rsi >= 60)                      $score += 2;
        elseif ($rsi >= 40 && $rsi <= 55)        $score += 1;

        if ($ind['macd_cross'] === 'bearish_cross')   $score += 2;
        elseif ($ind['macd_cross'] === 'bearish')     $score += 1;
        if ((float)$ind['macd_histogram'] < 0 && (float)$ind['macd'] < 0) $score += 1;

        if ($ind['ema_trend'] === 'bearish')          $score += 2;
        elseif ((float)$ind['ema9'] < (float)$ind['ema21']) $score += 1;

        if ($ind['bb_position'] === 'above_upper')    $score += 2;
        elseif ($ind['bb_position'] === 'upper_half') $score += 1;

        if ($ind['overall_trend'] === 'bearish')      $score += 1;

        return $score;
    }

    private function decideDirection(int $longScore, int $shortScore): string
    {
        if ($longScore < self::MIN_SIGNAL_SCORE && $shortScore < self::MIN_SIGNAL_SCORE) return 'none';
        if ($longScore > $shortScore && $longScore  >= self::MIN_SIGNAL_SCORE) return 'long';
        if ($shortScore > $longScore && $shortScore >= self::MIN_SIGNAL_SCORE) return 'short';
        return 'none';
    }

    private function calcEntryPrice(float $currentPrice, string $direction, float $atr): float
    {
        $offset = min($currentPrice * 0.0015, $atr * 0.5);
        return $direction === 'long' ? $currentPrice - $offset : $currentPrice + $offset;
    }

    private function calcConfidence(int $score, array $ind): float
    {
        $base       = min($score / 10 * 70, 70);
        $trendBonus = $ind['trend_strength'] > 50 ? min($ind['trend_strength'] / 100 * 20, 20) : 0;
        $rsi        = (float) $ind['rsi'];
        $rsiBonus   = ($rsi <= 30 || $rsi >= 70) ? 10 : 0;
        return min($base + $trendBonus + $rsiBonus, 100);
    }

    private function calcStrength(int $score): string
    {
        if ($score >= self::STRONG_SIGNAL_SCORE) return 'strong';
        if ($score >= self::MEDIUM_SIGNAL_SCORE) return 'medium';
        return 'weak';
    }

    private function suggestLeverage(float $atr, float $price, string $strength): int
    {
        $vol = $price > 0 ? ($atr / $price) * 100 : 1;

        if ($vol <= 0.5) {
            if ($strength === 'strong') return 20;
            if ($strength === 'medium') return 15;
            return 10;
        } elseif ($vol <= 1.0) {
            if ($strength === 'strong') return 15;
            if ($strength === 'medium') return 10;
            return 8;
        } elseif ($vol <= 2.0) {
            if ($strength === 'strong') return 10;
            if ($strength === 'medium') return 8;
            return 5;
        } else {
            if ($strength === 'strong') return 7;
            if ($strength === 'medium') return 5;
            return 3;
        }
    }

    private function suggestMode(string $strength, int $leverage, float $confidence): string
    {
        if ($leverage > 10)       return 'isolated';
        if ($strength === 'weak') return 'isolated';
        if ($confidence < 65)     return 'isolated';
        return 'cross';
    }
}
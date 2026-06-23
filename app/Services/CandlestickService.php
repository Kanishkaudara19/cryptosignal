<?php

namespace App\Services;

class CandlestickService
{
    /**
     * Analyze OHLCV data for common candlestick patterns.
     * 
     * @param array $ohlcv Array of candles (oldest first), each having [open, high, low, close, volume]
     * @return array Results of pattern matching for the LATEST candle
     */
    public function analyze(array $ohlcv): array
    {
        if (count($ohlcv) < 5) return [];

        $candles = array_values($ohlcv);
        $curr    = end($candles);
        $prev    = $candles[count($candles) - 2];
        $prev2   = $candles[count($candles) - 3];

        $results = [
            'patterns' => [],
            'bias'     => 'neutral',
            'strength' => 0
        ];

        // 1. Single Candle Patterns
        $this->analyzeSingle($curr, $prev, $results);

        // 2. Double Candle Patterns
        $this->analyzeDouble($curr, $prev, $results);

        // 3. Triple Candle Patterns
        $this->analyzeTriple($curr, $prev, $prev2, $results);

        return $results;
    }

    private function analyzeSingle(array $curr, array $prev, array &$results): void
    {
        $open  = (float)$curr['open'];
        $high  = (float)$curr['high'];
        $low   = (float)$curr['low'];
        $close = (float)$curr['close'];

        $bodySize  = abs($close - $open);
        $candleRange = $high - $low;
        if ($candleRange == 0) return;

        $upperWick = $high - max($open, $close);
        $lowerWick = min($open, $close) - $low;
        $isBullish = $close > $open;

        // Hammer (Bullish Reversal at bottom)
        if ($lowerWick > $bodySize * 2 && $upperWick < $bodySize * 0.5) {
            $results['patterns'][] = 'hammer';
            $results['bias'] = 'bullish';
            $results['strength'] += 2;
        }

        // Shooting Star (Bearish Reversal at top)
        if ($upperWick > $bodySize * 2 && $lowerWick < $bodySize * 0.5) {
            $results['patterns'][] = 'shooting_star';
            $results['bias'] = 'bearish';
            $results['strength'] += 2;
        }

        // Doji (Indecision)
        if ($bodySize < $candleRange * 0.1) {
            $results['patterns'][] = 'doji';
            // Doji doesn't change bias but signifies potential reversal
        }
    }

    private function analyzeDouble(array $curr, array $prev, array &$results): void
    {
        $cOpen  = (float)$curr['open'];
        $cClose = (float)$curr['close'];
        $pOpen  = (float)$prev['open'];
        $pClose = (float)$prev['close'];

        $cBodySize = abs($cClose - $cOpen);
        $pBodySize = abs($pClose - $pOpen);

        // Bullish Engulfing
        if ($cClose > $cOpen && $pClose < $pOpen && $cOpen <= $pClose && $cClose >= $pOpen && ($cBodySize > $pBodySize)) {
            $results['patterns'][] = 'bullish_engulfing';
            $results['bias'] = 'bullish';
            $results['strength'] += 3;
        }

        // Bearish Engulfing
        if ($cClose < $cOpen && $pClose > $pOpen && $cOpen >= $pClose && $cClose <= $pOpen && ($cBodySize > $pBodySize)) {
            $results['patterns'][] = 'bearish_engulfing';
            $results['bias'] = 'bearish';
            $results['strength'] += 3;
        }
    }

    private function analyzeTriple(array $curr, array $prev, array $prev2, array &$results): void
    {
        $cOpen  = (float)$curr['open'];
        $cClose = (float)$curr['close'];
        $pOpen  = (float)$prev['open'];
        $pClose = (float)$prev['close'];
        $p2Open = (float)$prev2['open'];
        $p2Close = (float)$prev2['close'];

        // Morning Star (Bullish) - P2: Bearish, P1: Small body (Doji/Spinning), Curr: Bullish
        if ($p2Close < $p2Open && abs($pOpen - $pClose) < abs($p2Open - $p2Close) * 0.3 && $cClose > $cOpen && $cClose > ($p2Open + $p2Close)/2) {
            $results['patterns'][] = 'morning_star';
            $results['bias'] = 'bullish';
            $results['strength'] += 4;
        }

        // Evening Star (Bearish)
        if ($p2Close > $p2Open && abs($pOpen - $pClose) < abs($p2Open - $p2Close) * 0.3 && $cClose < $cOpen && $cClose < ($p2Open + $p2Close)/2) {
            $results['patterns'][] = 'evening_star';
            $results['bias'] = 'bearish';
            $results['strength'] += 4;
        }
    }
}

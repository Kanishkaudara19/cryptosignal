<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndicatorCache extends Model
{
    protected $fillable = [
        'symbol', 'interval',
        'rsi', 'rsi_signal',
        'macd', 'macd_signal_line', 'macd_histogram', 'macd_cross',
        'ema9', 'ema21', 'ema50', 'ema_trend',
        'bb_upper', 'bb_middle', 'bb_lower', 'bb_bandwidth', 'bb_position',
        'overall_trend', 'trend_strength',
        'calculated_at',
    ];

    protected $casts = [
        'rsi'              => 'decimal:4',
        'macd'             => 'decimal:8',
        'macd_signal_line' => 'decimal:8',
        'macd_histogram'   => 'decimal:8',
        'ema9'             => 'decimal:8',
        'ema21'            => 'decimal:8',
        'ema50'            => 'decimal:8',
        'bb_upper'         => 'decimal:8',
        'bb_middle'        => 'decimal:8',
        'bb_lower'         => 'decimal:8',
        'bb_bandwidth'     => 'decimal:6',
        'trend_strength'   => 'decimal:2',
        'calculated_at'    => 'datetime',
    ];

    public function scopeForSymbol($query, string $symbol)
    {
        return $query->where('symbol', $symbol);
    }

    public function scopeForInterval($query, string $interval)
    {
        return $query->where('interval', $interval);
    }

    public function getIsBullishAttribute(): bool
    {
        return $this->overall_trend === 'bullish';
    }

    public function getIsBearishAttribute(): bool
    {
        return $this->overall_trend === 'bearish';
    }
}

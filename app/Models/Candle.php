<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Candle extends Model
{
    protected $fillable = [
        'symbol',
        'interval',
        'open_time',
        'close_time',
        'open',
        'high',
        'low',
        'close',
        'volume',
        'quote_volume',
        'num_trades',
    ];

    protected $casts = [
        'open'         => 'decimal:8',
        'high'         => 'decimal:8',
        'low'          => 'decimal:8',
        'close'        => 'decimal:8',
        'volume'       => 'decimal:8',
        'quote_volume' => 'decimal:8',
    ];

    public function coin(): BelongsTo
    {
        return $this->belongsTo(Coin::class, 'symbol', 'symbol');
    }

    public function scopeForSymbol($query, string $symbol)
    {
        return $query->where('symbol', $symbol);
    }

    public function scopeForInterval($query, string $interval)
    {
        return $query->where('interval', $interval);
    }

    public function scopeRecent($query, int $limit = 100)
    {
        return $query->orderBy('open_time', 'desc')->limit($limit);
    }

    /**
     * Get open time as Carbon instance
     */
    public function getOpenDateAttribute(): Carbon
    {
        return Carbon::createFromTimestampMs($this->open_time);
    }

    /**
     * Whether this is a bullish (green) candle
     */
    public function getIsBullishAttribute(): bool
    {
        return (float) $this->close >= (float) $this->open;
    }

    /**
     * Candle body size as a percentage of price
     */
    public function getBodySizePercentAttribute(): float
    {
        $open  = (float) $this->open;
        $close = (float) $this->close;
        return $open > 0 ? abs($close - $open) / $open * 100 : 0;
    }
}

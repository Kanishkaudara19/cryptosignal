<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signal extends Model
{
    protected $fillable = [
        'symbol', 'coin_name', 'interval',
        'trade_type', 'mode', 'entry_price', 'leverage',
        'take_profit_1', 'take_profit_2', 'take_profit_3', 'stop_loss',
        'confidence', 'signal_strength',
        'rsi', 'macd', 'macd_signal', 'ema9', 'ema21', 'ema50',
        'bb_upper', 'bb_lower', 'bb_middle',
        'status', 'close_price', 'pnl_percent', 'closed_at',
    ];

    protected $casts = [
        'entry_price'    => 'decimal:8',
        'take_profit_1'  => 'decimal:8',
        'take_profit_2'  => 'decimal:8',
        'take_profit_3'  => 'decimal:8',
        'stop_loss'      => 'decimal:8',
        'confidence'     => 'decimal:2',
        'rsi'            => 'decimal:4',
        'macd'           => 'decimal:8',
        'macd_signal'    => 'decimal:8',
        'ema9'           => 'decimal:8',
        'ema21'          => 'decimal:8',
        'ema50'          => 'decimal:8',
        'bb_upper'       => 'decimal:8',
        'bb_lower'       => 'decimal:8',
        'bb_middle'      => 'decimal:8',
        'close_price'    => 'decimal:8',
        'pnl_percent'    => 'decimal:4',
        'closed_at'      => 'datetime',
    ];

    public function coin(): BelongsTo
    {
        return $this->belongsTo(Coin::class, 'symbol', 'symbol');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeLong($query)
    {
        return $query->where('trade_type', 'long');
    }

    public function scopeShort($query)
    {
        return $query->where('trade_type', 'short');
    }

    public function getIsLongAttribute(): bool
    {
        return $this->trade_type === 'long';
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Risk/reward ratio
     */
    public function getRiskRewardAttribute(): float
    {
        $entry = (float) $this->entry_price;
        $sl    = (float) $this->stop_loss;
        $tp1   = (float) $this->take_profit_1;

        $risk   = abs($entry - $sl);
        $reward = abs($tp1 - $entry);

        return $risk > 0 ? round($reward / $risk, 2) : 0;
    }

    /**
     * TP1 potential gain as a percentage
     */
    public function getTp1PercentAttribute(): float
    {
        $entry = (float) $this->entry_price;
        $tp1   = (float) $this->take_profit_1;
        return $entry > 0 ? round(abs($tp1 - $entry) / $entry * 100, 2) : 0;
    }

    /**
     * Stop loss risk as a percentage
     */
    public function getSlPercentAttribute(): float
    {
        $entry = (float) $this->entry_price;
        $sl    = (float) $this->stop_loss;
        return $entry > 0 ? round(abs($entry - $sl) / $entry * 100, 2) : 0;
    }
}

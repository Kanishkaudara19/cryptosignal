<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coin extends Model
{
    protected $fillable = [
        'symbol',
        'base_asset',
        'quote_asset',
        'name',
        'is_active',
        'last_price',
        'price_change_24h',
        'volume_24h',
        'high_24h',
        'low_24h',
        'last_updated',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'last_price'       => 'decimal:8',
        'price_change_24h' => 'decimal:4',
        'volume_24h'       => 'decimal:8',
        'high_24h'         => 'decimal:8',
        'low_24h'          => 'decimal:8',
        'last_updated'     => 'datetime',
    ];

    public function candles(): HasMany
    {
        return $this->hasMany(Candle::class, 'symbol', 'symbol');
    }

    public function signals(): HasMany
    {
        return $this->hasMany(Signal::class, 'symbol', 'symbol');
    }

    public function indicatorCache(): HasMany
    {
        return $this->hasMany(IndicatorCache::class, 'symbol', 'symbol');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get display label e.g. "BTC / USDT"
     */
    public function getDisplayLabelAttribute(): string
    {
        return "{$this->base_asset} / {$this->quote_asset}";
    }

    /**
     * Format price with appropriate decimal places
     */
    public function getFormattedPriceAttribute(): string
    {
        $price = (float) $this->last_price;
        if ($price >= 1000) {
            return number_format($price, 2);
        } elseif ($price >= 1) {
            return number_format($price, 4);
        } else {
            return number_format($price, 6);
        }
    }
}

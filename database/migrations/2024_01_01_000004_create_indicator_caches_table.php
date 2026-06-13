<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_caches', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 20);
            $table->string('interval', 5);

            // RSI
            $table->decimal('rsi', 8, 4)->nullable();
            $table->string('rsi_signal', 20)->nullable(); // oversold, overbought, neutral

            // MACD
            $table->decimal('macd', 20, 8)->nullable();
            $table->decimal('macd_signal_line', 20, 8)->nullable();
            $table->decimal('macd_histogram', 20, 8)->nullable();
            $table->string('macd_cross', 20)->nullable();  // bullish_cross, bearish_cross, none

            // EMA
            $table->decimal('ema9', 20, 8)->nullable();
            $table->decimal('ema21', 20, 8)->nullable();
            $table->decimal('ema50', 20, 8)->nullable();
            $table->string('ema_trend', 20)->nullable();   // bullish, bearish, neutral

            // Bollinger Bands
            $table->decimal('bb_upper', 20, 8)->nullable();
            $table->decimal('bb_middle', 20, 8)->nullable();
            $table->decimal('bb_lower', 20, 8)->nullable();
            $table->decimal('bb_bandwidth', 10, 6)->nullable();
            $table->string('bb_position', 20)->nullable(); // above_upper, below_lower, inside

            // Overall trend
            $table->string('overall_trend', 20)->nullable(); // bullish, bearish, neutral
            $table->decimal('trend_strength', 5, 2)->nullable(); // 0-100

            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->unique(['symbol', 'interval']);
            $table->index(['symbol', 'interval']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_caches');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signals', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 20);
            $table->string('coin_name', 50);
            $table->string('interval', 5);

            // Signal core
            $table->enum('trade_type', ['long', 'short']);
            $table->enum('mode', ['cross', 'isolated'])->default('isolated');
            $table->decimal('entry_price', 20, 8);
            $table->integer('leverage')->default(10);

            // Targets
            $table->decimal('take_profit_1', 20, 8);
            $table->decimal('take_profit_2', 20, 8);
            $table->decimal('take_profit_3', 20, 8);
            $table->decimal('stop_loss', 20, 8);

            // Signal quality
            $table->decimal('confidence', 5, 2)->default(0);  // 0-100%
            $table->string('signal_strength', 10)->default('medium'); // weak, medium, strong

            // Indicator snapshots at time of signal
            $table->decimal('rsi', 8, 4)->nullable();
            $table->decimal('macd', 20, 8)->nullable();
            $table->decimal('macd_signal', 20, 8)->nullable();
            $table->decimal('ema9', 20, 8)->nullable();
            $table->decimal('ema21', 20, 8)->nullable();
            $table->decimal('ema50', 20, 8)->nullable();
            $table->decimal('bb_upper', 20, 8)->nullable();
            $table->decimal('bb_lower', 20, 8)->nullable();
            $table->decimal('bb_middle', 20, 8)->nullable();

            // Outcome tracking (filled in later)
            $table->enum('status', ['active', 'tp1_hit', 'tp2_hit', 'tp3_hit', 'sl_hit', 'expired'])
                  ->default('active');
            $table->decimal('close_price', 20, 8)->nullable();
            $table->decimal('pnl_percent', 10, 4)->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();
            $table->index(['symbol', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signals');
    }
};

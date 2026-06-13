<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candles', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 20);
            $table->string('interval', 5);                 // 1m, 5m, 15m, 1h, 4h, 1d, 1w
            $table->bigInteger('open_time');               // Unix ms timestamp
            $table->bigInteger('close_time');
            $table->decimal('open', 20, 8);
            $table->decimal('high', 20, 8);
            $table->decimal('low', 20, 8);
            $table->decimal('close', 20, 8);
            $table->decimal('volume', 30, 8);
            $table->decimal('quote_volume', 30, 8)->nullable();
            $table->integer('num_trades')->default(0);
            $table->timestamps();

            // Each candle is unique per symbol + interval + open_time
            $table->unique(['symbol', 'interval', 'open_time']);
            $table->index(['symbol', 'interval', 'open_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candles');
    }
};

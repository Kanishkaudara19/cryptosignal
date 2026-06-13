<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coins', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 20)->unique();        // e.g. BTCUSDT
            $table->string('base_asset', 10);              // e.g. BTC
            $table->string('quote_asset', 10)->default('USDT');
            $table->string('name', 50);                    // e.g. Bitcoin
            $table->boolean('is_active')->default(true);
            $table->decimal('last_price', 20, 8)->nullable();
            $table->decimal('price_change_24h', 10, 4)->nullable();   // percent
            $table->decimal('volume_24h', 30, 8)->nullable();
            $table->decimal('high_24h', 20, 8)->nullable();
            $table->decimal('low_24h', 20, 8)->nullable();
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coins');
    }
};

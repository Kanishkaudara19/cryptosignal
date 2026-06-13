<?php

namespace App\Console\Commands;

use App\Models\Coin;
use App\Services\BinanceService;
use Illuminate\Console\Command;

class FetchCandles extends Command
{
    protected $signature = 'crypto:fetch-candles
                            {--symbol=   : Specific symbol (default: all active coins)}
                            {--interval= : Timeframe (default: 15m)}
                            {--limit=100 : Number of candles}';

    protected $description = 'Fetch and cache OHLCV candles from Binance for all active coins';

    public function handle(BinanceService $binance): int
    {
        $symbol   = $this->option('symbol')   ? strtoupper($this->option('symbol')) : null;
        $interval = $this->option('interval') ?? '15m';
        $limit    = (int) ($this->option('limit') ?? 100);

        $symbols = $symbol
            ? [$symbol]
            : Coin::active()->pluck('symbol')->toArray();

        if (empty($symbols)) {
            $this->warn('No active coins found. Run: php artisan db:seed --class=CoinSeeder');
            return self::FAILURE;
        }

        $this->info("Fetching {$interval} candles for " . count($symbols) . " coin(s)...");
        $bar = $this->output->createProgressBar(count($symbols));
        $bar->start();

        $success = 0;
        $failed  = 0;

        foreach ($symbols as $sym) {
            try {
                $candles = $binance->getCandles($sym, $interval, $limit, true);
                $this->line("  ✓ {$sym}: " . count($candles) . " candles");
                $success++;
            } catch (\Exception $e) {
                $this->error("  ✗ {$sym}: " . $e->getMessage());
                $failed++;
            }
            $bar->advance();
            usleep(200000); // 200ms delay to respect Binance rate limits
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. Success: {$success} | Failed: {$failed}");

        return self::SUCCESS;
    }
}

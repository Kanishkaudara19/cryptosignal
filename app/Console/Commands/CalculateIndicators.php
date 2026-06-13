<?php

namespace App\Console\Commands;

use App\Models\Coin;
use App\Services\IndicatorService;
use Illuminate\Console\Command;

class CalculateIndicators extends Command
{
    protected $signature = 'crypto:calculate-indicators
                            {--symbol=    : Specific symbol (default: all active coins)}
                            {--interval=  : Timeframe (default: 15m)}
                            {--all        : Calculate for all intervals: 15m, 1h, 4h, 1d}';

    protected $description = 'Calculate and cache RSI, MACD, EMA, Bollinger Bands for all active coins';

    public function handle(IndicatorService $indicators): int
    {
        $symbol    = $this->option('symbol') ? strtoupper($this->option('symbol')) : null;
        $allTf     = $this->option('all');
        $intervals = $allTf
            ? ['15m', '1h', '4h', '1d']
            : [$this->option('interval') ?? '15m'];

        $symbols = $symbol
            ? [$symbol]
            : Coin::active()->pluck('symbol')->toArray();

        if (empty($symbols)) {
            $this->warn('No active coins found.');
            return self::FAILURE;
        }

        $total   = count($symbols) * count($intervals);
        $success = 0;
        $failed  = 0;

        $this->info("Calculating indicators for " . count($symbols) . " coin(s) × " . count($intervals) . " interval(s) = {$total} jobs");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($symbols as $sym) {
            foreach ($intervals as $interval) {
                try {
                    $result = $indicators->calculate($sym, $interval, true);
                    $this->line(sprintf(
                        "  ✓ %s %s | RSI %.1f | %s | Trend: %s (%.0f%%)",
                        $sym, $interval,
                        $result['rsi'],
                        strtoupper($result['rsi_signal']),
                        $result['overall_trend'],
                        $result['trend_strength']
                    ));
                    $success++;
                } catch (\Exception $e) {
                    $this->error("  ✗ {$sym} {$interval}: " . $e->getMessage());
                    $failed++;
                }
                $bar->advance();
                usleep(100000); // 100ms — respect Binance rate limits
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. Success: {$success} | Failed: {$failed}");

        return self::SUCCESS;
    }
}

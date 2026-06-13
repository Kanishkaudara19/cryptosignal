<?php

namespace App\Console\Commands;

use App\Models\Signal;
use App\Services\BinanceService;
use Illuminate\Console\Command;

class UpdateSignalStatus extends Command
{
    protected $signature   = 'crypto:update-signal-status';
    protected $description = 'Check active signals and update status when TP or SL is hit';

    public function handle(BinanceService $binance): int
    {
        $activeSignals = Signal::where('status', 'active')->get();

        if ($activeSignals->isEmpty()) {
            $this->info('No active signals to check.');
            return self::SUCCESS;
        }

        $this->info("Checking {$activeSignals->count()} active signal(s)...");
        $updated = 0;

        foreach ($activeSignals as $signal) {
            try {
                $price = $binance->getCurrentPrice($signal->symbol);

                $hit = $this->checkHit($signal, $price);

                if ($hit) {
                    $signal->update([
                        'status'      => $hit,
                        'close_price' => $price,
                        'pnl_percent' => $this->calcPnl($signal, $price),
                        'closed_at'   => now(),
                    ]);

                    $emoji = str_starts_with($hit, 'tp') ? '✅' : '❌';
                    $this->line("  {$emoji} {$signal->symbol} → {$hit} @ \${$price}");
                    $updated++;
                }

                usleep(100000); // 100ms rate limit

            } catch (\Exception $e) {
                $this->error("  ✗ {$signal->symbol}: " . $e->getMessage());
            }
        }

        $this->info("Updated: {$updated} signal(s).");
        return self::SUCCESS;
    }

    /**
     * Check if any TP or SL level has been hit.
     * Returns the status string or null if nothing hit yet.
     */
    private function checkHit(Signal $signal, float $price): ?string
    {
        $isLong = $signal->trade_type === 'long';

        // Stop loss hit
        if ($isLong  && $price <= (float) $signal->stop_loss) return 'sl_hit';
        if (!$isLong && $price >= (float) $signal->stop_loss) return 'sl_hit';

        // TP3 hit (check highest first)
        if ($isLong  && $price >= (float) $signal->take_profit_3) return 'tp3_hit';
        if (!$isLong && $price <= (float) $signal->take_profit_3) return 'tp3_hit';

        // TP2 hit
        if ($isLong  && $price >= (float) $signal->take_profit_2) return 'tp2_hit';
        if (!$isLong && $price <= (float) $signal->take_profit_2) return 'tp2_hit';

        // TP1 hit
        if ($isLong  && $price >= (float) $signal->take_profit_1) return 'tp1_hit';
        if (!$isLong && $price <= (float) $signal->take_profit_1) return 'tp1_hit';

        return null;
    }

    /**
     * Calculate PnL % at close price.
     */
    private function calcPnl(Signal $signal, float $closePrice): float
    {
        $entry = (float) $signal->entry_price;
        if ($entry <= 0) return 0;

        $pnl = $signal->trade_type === 'long'
            ? ($closePrice - $entry) / $entry * 100
            : ($entry - $closePrice) / $entry * 100;

        return round($pnl * $signal->leverage, 4);
    }
}

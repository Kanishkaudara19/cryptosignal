<?php

namespace App\Console\Commands;

use App\Models\Coin;
use App\Services\SignalService;
use Illuminate\Console\Command;

class GenerateSignals extends Command
{
    protected $signature = 'crypto:generate-signals
                            {--symbol=   : Specific symbol (default: all active coins)}
                            {--interval= : Timeframe (default: 15m)}';

    protected $description = 'Generate and save trade signals for all active coins';

    public function handle(SignalService $signals): int
    {
        $symbol   = $this->option('symbol') ? strtoupper($this->option('symbol')) : null;
        $interval = $this->option('interval') ?? '15m';

        $symbols = $symbol
            ? [$symbol]
            : Coin::active()->pluck('symbol')->toArray();

        if (empty($symbols)) {
            $this->warn('No active coins found.');
            return self::FAILURE;
        }

        $this->info("Generating {$interval} signals for " . count($symbols) . " coin(s)...\n");

        $generated = 0;
        $skipped   = 0;

        foreach ($symbols as $sym) {
            try {
                $signal = $signals->generate($sym, $interval);

                $type  = strtoupper($signal['trade_type']);
                $entry = number_format($signal['entry_price'], 4);
                $conf  = $signal['confidence'];
                $str   = strtoupper($signal['signal_strength']);
                $lev   = $signal['leverage'];

                $color = $signal['trade_type'] === 'long' ? 'green' : 'red';

                $this->line(sprintf(
                    "  <fg=%s>✓ %s %s</> | Entry: $%s | Lev: %sx | Conf: %.1f%% | %s",
                    $color, $sym, $type, $entry, $lev, $conf, $str
                ));
                $this->line(sprintf(
                    "       TP1: $%s  TP2: $%s  TP3: $%s  SL: $%s",
                    number_format($signal['take_profit_1'], 4),
                    number_format($signal['take_profit_2'], 4),
                    number_format($signal['take_profit_3'], 4),
                    number_format($signal['stop_loss'], 4)
                ));

                $generated++;

            } catch (\RuntimeException $e) {
                $this->line("  <fg=yellow>— {$sym}: {$e->getMessage()}</>");
                $skipped++;
            } catch (\Exception $e) {
                $this->error("  ✗ {$sym}: " . $e->getMessage());
                $skipped++;
            }

            usleep(300000); // 300ms — respect Binance rate limits
        }

        $this->newLine();
        $this->info("Generated: {$generated} | Skipped (neutral): {$skipped}");

        return self::SUCCESS;
    }
}

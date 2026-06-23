<?php

namespace App\Console\Commands;

use App\Models\Coin;
use App\Services\SignalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateBestSignal extends Command
{
    protected $signature = 'crypto:generate-best-signal
                            {--interval= : Timeframe (default: 1h)}
                            {--min-confidence=70 : Minimum confidence threshold}';

    protected $description = 'Evaluate all active coins and generate the single best signal';

    public function handle(SignalService $signals): int
    {
        $interval      = $this->option('interval') ?? '1h';
        $minConfidence = (float) $this->option('min-confidence');

        $symbols = Coin::active()->pluck('symbol')->toArray();

        if (empty($symbols)) {
            $this->warn('No active coins found.');
            return self::FAILURE;
        }

        $this->info("Scanning " . count($symbols) . " coins for the best {$interval} signal (min confidence: {$minConfidence}%)...");

        $bestCandidate = null;
        $maxConfidence = 0;

        $bar = $this->output->createProgressBar(count($symbols));
        $bar->start();

        foreach ($symbols as $symbol) {
            try {
                $candidate = $signals->evaluate($symbol, $interval);
                
                if ($candidate['confidence'] > $maxConfidence) {
                    $maxConfidence = $candidate['confidence'];
                    $bestCandidate = $candidate;
                }
            } catch (\Exception $e) {
                // Skip coins with no signal or errors
            }
            
            $bar->advance();
            usleep(100000); // 100ms - respect rate limits during scan
        }

        $bar->finish();
        $this->newLine(2);

        if (!$bestCandidate || $maxConfidence < $minConfidence) {
            $this->warn("No signals found above {$minConfidence}% confidence threshold.");
            return self::SUCCESS;
        }

        // Generate and save the best one
        $this->info("Best signal found: {$bestCandidate['symbol']} ({$bestCandidate['trade_type']}) with {$maxConfidence}% confidence!");
        
        $finalSignal = $signals->generate($bestCandidate['symbol'], $interval, 'auto');

        $this->table(
            ['Property', 'Value'],
            [
                ['Symbol', $finalSignal['symbol']],
                ['Type', strtoupper($finalSignal['trade_type'])],
                ['Confidence', $finalSignal['confidence'] . '%'],
                ['Strength', $finalSignal['signal_strength']],
                ['Entry', $finalSignal['entry_price']],
                ['TP1', $finalSignal['take_profit_1']],
                ['SL', $finalSignal['stop_loss']],
            ]
        );

        Log::info("30-minute best signal generated: {$finalSignal['symbol']} @ {$finalSignal['entry_price']}");

        return self::SUCCESS;
    }
}

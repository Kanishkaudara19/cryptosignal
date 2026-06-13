<?php

namespace App\Console\Commands;

use App\Services\BinanceService;
use Illuminate\Console\Command;

class RefreshPrices extends Command
{
    protected $signature   = 'crypto:refresh-prices';
    protected $description = 'Refresh last_price for all active coins from Binance';

    public function handle(BinanceService $binance): int
    {
        $this->info('Refreshing coin prices...');

        try {
            $count = $binance->refreshAllCoinPrices();
            $this->info("Updated {$count} coin(s) successfully.");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

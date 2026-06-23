<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('crypto:refresh-prices')->everyMinute();

        $schedule->command('crypto:fetch-candles --interval=15m --limit=100')->everyFifteenMinutes();
        $schedule->command('crypto:fetch-candles --interval=1h  --limit=100')->hourly();
        $schedule->command('crypto:fetch-candles --interval=4h  --limit=100')->everyFourHours();
        $schedule->command('crypto:fetch-candles --interval=1d  --limit=100')->daily();

        $schedule->command('crypto:calculate-indicators --interval=15m')->everyFiveMinutes();
        $schedule->command('crypto:calculate-indicators --interval=1h')->everyThirtyMinutes();

        // Stop frequent auto-generation as per user request
        // $schedule->command('crypto:generate-signals --interval=15m')->everyFiveMinutes();
        // $schedule->command('crypto:generate-signals --interval=1h')->everyThirtyMinutes();

        // New: Automatically select and generate the best signal once every hour
        $schedule->command('crypto:generate-best-signal --interval=1h --min-confidence=50')->hourly();

        $schedule->command('crypto:update-signal-status')->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

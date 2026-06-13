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

    $schedule->command('crypto:calculate-indicators --interval=15m')->everyFifteenMinutes();
    $schedule->command('crypto:calculate-indicators --interval=1h')->hourly();
    $schedule->command('crypto:calculate-indicators --interval=4h')->everyFourHours();
    $schedule->command('crypto:calculate-indicators --interval=1d')->daily();

    $schedule->command('crypto:generate-signals --interval=15m')->everyFifteenMinutes();
    $schedule->command('crypto:generate-signals --interval=1h')->hourly();
    $schedule->command('crypto:generate-signals --interval=4h')->everyFourHours();

    $schedule->command('crypto:update-signal-status')->everyTwoMinutes();
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

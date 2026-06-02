<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\ProcessTapRecordSyncQueue::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Run queue processor every minute to keep attendance in sync for external writers
        $schedule->command('tap-records:process-queue --limit=200')->everyMinute();
    }

    protected function commands(): void
    {
        // load default commands
    }
}

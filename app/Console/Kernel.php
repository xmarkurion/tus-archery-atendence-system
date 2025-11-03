<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
//    protected function schedule(Schedule $schedule): void
//    {
//        // debug: log when schedule() is executed by CLI (helps diagnose why no events are registered)
//        file_put_contents(storage_path('logs/schedule-kernel.log'), date('c') . " schedule() called\n", FILE_APPEND);
//
//        // Run daily at midnight - adjust as needed
////        $schedule->command('meetings:create-daily')->daily();
//        $schedule->command('meetings:create-daily')
//            ->everyMinute()
//            ->withoutOverlapping()
//            ->runInBackground()
//            ->appendOutputTo(storage_path('logs/schedule.log'));
//    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

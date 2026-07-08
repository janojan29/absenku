<?php

namespace App\Console;

use App\Console\Commands\MarkAbsentAttendances;
use App\Console\Commands\MarkMissingCheckoutAttendances;
use App\Console\Commands\RunScheduledAttendanceTasks;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        MarkAbsentAttendances::class,
        MarkMissingCheckoutAttendances::class,
        RunScheduledAttendanceTasks::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * The scheduler runs the unified attendance task checker every minute.
     * The command itself uses Cache::add() to ensure each sub-task only
     * runs once per day, so running every minute has no negative impact
     * but guarantees near-instant execution once the deadline passes.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ── Primary: run the unified task every minute ───────────────────
        // This checks the current time against school settings and fires
        // mark-absent / mark-missing-checkout exactly once per day when
        // the respective deadline has passed. No user login required.
        $schedule
            ->command('attendance:run-scheduled')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}

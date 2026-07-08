<?php

namespace App\Console\Commands;

use App\Models\SchoolSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * A single command that checks the current time against school settings
 * and runs mark-absent / mark-missing-checkout when their respective
 * deadlines have passed. Uses Cache::add() to guarantee each task
 * runs at most once per day, regardless of how many times this command
 * is invoked.
 *
 * Designed to be called by a simple system cron every minute:
 *   * * * * * cd /path/to/project && php artisan attendance:run-scheduled >> /dev/null 2>&1
 */
class RunScheduledAttendanceTasks extends Command
{
    protected $signature = 'attendance:run-scheduled';

    protected $description = 'Check deadlines and run attendance tasks (mark-absent, mark-missing-checkout) if due.';

    public function handle(): int
    {
        try {
            $setting = SchoolSetting::singleton();
        } catch (\Throwable $e) {
            $this->error('Could not load school settings: ' . $e->getMessage());
            return self::FAILURE;
        }

        $today = Carbon::today();
        $now = now();

        // ── Mark Absent: langsung saat waktu absen masuk habis ───────────
        $markAbsentAt = Carbon::today()
            ->setTimeFromTimeString((string) $setting->check_in_end_time);

        if ($now->greaterThanOrEqualTo($markAbsentAt)) {
            $absentKey = 'attendance:mark-absent:' . $today->toDateString();
            if (Cache::add($absentKey, true, $today->copy()->endOfDay())) {
                $this->info('Running attendance:mark-absent for ' . $today->toDateString());
                $this->call('attendance:mark-absent', [
                    '--date' => $today->toDateString(),
                ]);
            } else {
                $this->info('attendance:mark-absent already ran today.');
            }
        }

        // ── Mark Missing Checkout: langsung saat waktu absen pulang habis ─
        $missingCheckoutAt = Carbon::today()
            ->setTimeFromTimeString((string) $setting->check_out_end_time);

        if ($now->greaterThanOrEqualTo($missingCheckoutAt)) {
            $missingKey = 'attendance:mark-missing-checkout:' . $today->toDateString();
            if (Cache::add($missingKey, true, $today->copy()->endOfDay())) {
                $this->info('Running attendance:mark-missing-checkout for ' . $today->toDateString());
                $this->call('attendance:mark-missing-checkout', [
                    '--date' => $today->toDateString(),
                ]);
            } else {
                $this->info('attendance:mark-missing-checkout already ran today.');
            }
        }

        return self::SUCCESS;
    }
}

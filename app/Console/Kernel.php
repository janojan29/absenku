<?php

namespace App\Console;

use App\Console\Commands\MarkAbsentAttendances;
use App\Console\Commands\MarkMissingCheckoutAttendances;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        MarkAbsentAttendances::class,
        MarkMissingCheckoutAttendances::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $time = env('ATTENDANCE_ABSENT_MARK_TIME', '08:30');
        $missingCheckoutTime = env('ATTENDANCE_MISSING_CHECKOUT_TIME', '17:00');

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('school_settings')) {
                $setting = \App\Models\SchoolSetting::first();
                if ($setting && $setting->check_in_end_time && $setting->late_tolerance_minutes !== null) {
                    // Jadwalkan 5 menit setelah batas waktu toleransi terakhir
                    $time = \Illuminate\Support\Carbon::parse($setting->check_in_end_time)
                        ->addMinutes($setting->late_tolerance_minutes)
                        ->addMinutes(5)
                        ->format('H:i');
                }

                if ($setting && $setting->check_out_end_time) {
                    $missingCheckoutTime = \Illuminate\Support\Carbon::parse($setting->check_out_end_time)
                        ->addMinutes(5)
                        ->format('H:i');
                }
            }
        } catch (\Throwable $e) {
            // Jika database belum siap (misal saat migrate), gunakan nilai default dari env
        }

        $schedule
            ->command('attendance:mark-absent')
            ->dailyAt($time)
            ->onOneServer()
            ->withoutOverlapping();

        $schedule
            ->command('attendance:mark-missing-checkout')
            ->dailyAt($missingCheckoutTime)
            ->onOneServer()
            ->withoutOverlapping();
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

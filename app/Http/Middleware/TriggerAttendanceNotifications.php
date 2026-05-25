<?php

namespace App\Http\Middleware;

use App\Models\SchoolSetting;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class TriggerAttendanceNotifications
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->runningInConsole()) {
            return $next($request);
        }

        try {
            $setting = SchoolSetting::singleton();
            $today = Carbon::today();

            $markAbsentAt = Carbon::today()
                ->setTimeFromTimeString((string) $setting->check_in_end_time)
                ->addMinutes((int) $setting->late_tolerance_minutes)
                ->addMinutes(5);

            $missingCheckoutAt = Carbon::today()
                ->setTimeFromTimeString((string) $setting->check_out_end_time)
                ->addMinutes(5);

            if (now()->greaterThanOrEqualTo($markAbsentAt)) {
                $absentKey = 'attendance:mark-absent:' . $today->toDateString();
                if (Cache::add($absentKey, true, $today->copy()->endOfDay())) {
                    Artisan::call('attendance:mark-absent', [
                        '--date' => $today->toDateString(),
                    ]);
                }
            }

            if (now()->greaterThanOrEqualTo($missingCheckoutAt)) {
                $missingKey = 'attendance:mark-missing-checkout:' . $today->toDateString();
                if (Cache::add($missingKey, true, $today->copy()->endOfDay())) {
                    Artisan::call('attendance:mark-missing-checkout', [
                        '--date' => $today->toDateString(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Avoid breaking the request path if the notification trigger fails.
        }

        return $next($request);
    }
}

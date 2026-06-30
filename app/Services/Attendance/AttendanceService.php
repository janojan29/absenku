<?php

namespace App\Services\Attendance;

use App\Events\AttendanceUpdated;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Services\Geo\HaversineDistance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    /** GPS accuracy below this value (meters) is suspiciously perfect → likely fake GPS */
    private const MIN_ACCURACY_METERS = 5.0;

    /** GPS accuracy above this value (meters) is too imprecise to trust */
    private const MAX_ACCURACY_METERS = 100.0;

    public function __construct(private readonly HaversineDistance $distance) {}

    public function checkIn(User $user, float $latitude, float $longitude, float $accuracy): string
    {
        $setting = SchoolSetting::singleton();
        if (!$setting->is_attendance_active) {
            throw new \Exception('Sistem absensi sedang dinonaktifkan.');
        }

        $this->validateAccuracy($accuracy);

        $today = Carbon::today();

        if (\App\Helpers\HolidayHelper::isHoliday($today)) {
            throw ValidationException::withMessages([
                'check_in' => 'Hari ini adalah hari libur. Absen masuk dinonaktifkan.',
            ]);
        }

        $hasApprovedLeaveToday = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->whereIn('type', ['absent', 'early_leave'])
            ->where('status', 'approved')
            ->exists();

        $hasApprovedAbsentLeave = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->where('type', 'absent')
            ->where('status', 'approved')
            ->exists();

        if ($hasApprovedAbsentLeave) {
            throw ValidationException::withMessages([
                'check_in' => 'Izin tidak masuk kamu untuk hari ini sudah disetujui, jadi absen masuk dinonaktifkan.',
            ]);
        }

        $meters = $this->distance->meters(
            $latitude,
            $longitude,
            (float) $setting->latitude,
            (float) $setting->longitude,
        );

        if ($meters > (float) $setting->radius_meters) {
            throw ValidationException::withMessages([
                'geo' => 'Lokasi kamu di luar area sekolah (' . (int) round($meters) . ' m).',
            ]);
        }

        $start = Carbon::today()->setTimeFromTimeString($setting->check_in_start_time);
        $end = Carbon::today()->setTimeFromTimeString($setting->check_in_end_time);
        $lateAt = (clone $end)->subMinutes((int) $setting->late_tolerance_minutes);
        $now = now();

        if ($now->lessThan($start)) {
            throw ValidationException::withMessages([
                'check_in' => 'Waktu absen masuk belum dimulai. Buka pada jam ' . substr((string) $setting->check_in_start_time, 0, 5) . '.',
            ]);
        }

        if ($now->greaterThan($end)) {
            throw ValidationException::withMessages([
                'check_in' => 'Waktu absen masuk sudah berakhir pada jam ' . substr((string) $setting->check_in_end_time, 0, 5) . '.',
            ]);
        }

        $status = $now->greaterThan($lateAt) ? 'late' : 'present';
        $lateMinutes = $status === 'late' ? (int) abs($now->diffInMinutes($lateAt)) : null;

        $alreadyCheckedIn = false;

        DB::transaction(function () use ($user, $today, $meters, $status, $lateMinutes, $latitude, $longitude, $accuracy, $hasApprovedLeaveToday, &$alreadyCheckedIn) {
            $attendance = Attendance::query()->firstOrCreate(
                ['user_id' => $user->id, 'date' => $today->toDateString()],
            );

            if ($attendance->check_in_at !== null) {
                $alreadyCheckedIn = true;
                return;
            }

            $attendance->update([
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'check_in_at' => now(),
                'check_in_latitude' => $latitude,
                'check_in_longitude' => $longitude,
                'check_in_distance_meters' => (int) round($meters),
                'check_in_accuracy' => round($accuracy, 2),
            ]);

            $classRoomId = (int) ($user->studentProfile?->class_room_id ?? 0);
            if ($classRoomId > 0) {
                event(new AttendanceUpdated($attendance->fresh(), $classRoomId));
            }

            if ($status === 'late' && ! $hasApprovedLeaveToday) {
                $message = 'Informasi: ' . $user->name . ' terlambat absen masuk pada ' . now()->format('d/m/Y H:i') . '. (Status: TERLAMBAT)';
                $parentWa = $user->studentProfile?->parent_phone_wa ?: $user->studentProfile?->parent_whatsapp_number;
                if (! empty($parentWa)) {
                    SendWhatsAppMessage::dispatch(
                        to: $parentWa,
                        message: $message,
                        relatedType: Attendance::class,
                        relatedId: $attendance->id,
                    );
                }
            }
        });

        return $alreadyCheckedIn ? 'Kamu sudah absen masuk.' : 'Absen masuk berhasil.';
    }

    public function checkOut(User $user, float $latitude, float $longitude, float $accuracy): string
    {
        $this->validateAccuracy($accuracy);

        $setting = SchoolSetting::singleton();
        if (!$setting->is_attendance_active) {
            throw new \Exception('Sistem absensi sedang dinonaktifkan.');
        }

        $today = Carbon::today();

        if (\App\Helpers\HolidayHelper::isHoliday($today)) {
            throw ValidationException::withMessages([
                'check_out' => 'Hari ini adalah hari libur. Absen pulang dinonaktifkan.',
            ]);
        }

        $hasApprovedAbsentLeave = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->where('type', 'absent')
            ->where('status', 'approved')
            ->exists();

        if ($hasApprovedAbsentLeave) {
            throw ValidationException::withMessages([
                'check_out' => 'Izin tidak masuk kamu untuk hari ini sudah disetujui, jadi absen pulang dinonaktifkan.',
            ]);
        }

        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if (! $attendance || $attendance->check_in_at === null) {
            throw ValidationException::withMessages([
                'check_out' => 'Kamu belum absen masuk hari ini.',
            ]);
        }

        if ($attendance->check_out_at !== null) {
            return 'Kamu sudah absen pulang.';
        }

        $checkOutStart = Carbon::today()->setTimeFromTimeString($setting->check_out_start_time);
        $checkOutEnd = Carbon::today()->setTimeFromTimeString($setting->check_out_end_time);

        $now = now();
        if ($now->lessThan($checkOutStart)) {
            throw ValidationException::withMessages([
                'check_out' => 'Absen pulang baru bisa setelah jam ' . substr((string) $setting->check_out_start_time, 0, 5) . '.',
            ]);
        }

        if ($now->greaterThan($checkOutEnd)) {
            throw ValidationException::withMessages([
                'check_out' => 'Waktu absen pulang sudah berakhir pada jam ' . substr((string) $setting->check_out_end_time, 0, 5) . '.',
            ]);
        }

        $meters = $this->distance->meters(
            $latitude,
            $longitude,
            (float) $setting->latitude,
            (float) $setting->longitude,
        );

        if ($meters > (float) $setting->radius_meters) {
            throw ValidationException::withMessages([
                'geo' => 'Lokasi kamu di luar area sekolah (' . (int) round($meters) . ' m).',
            ]);
        }

        DB::transaction(function () use ($attendance, $user, $meters, $latitude, $longitude, $accuracy) {
            $attendance->update([
                'check_out_at' => now(),
                'check_out_late_minutes' => null,
                'check_out_latitude' => $latitude,
                'check_out_longitude' => $longitude,
                'check_out_distance_meters' => (int) round($meters),
                'check_out_accuracy' => round($accuracy, 2),
            ]);

            $classRoomId = (int) ($user->studentProfile?->class_room_id ?? 0);
            if ($classRoomId > 0) {
                event(new AttendanceUpdated($attendance->fresh(), $classRoomId));
            }

            // Tidak ada notifikasi untuk absen pulang sesuai ketentuan.
        });

        return 'Absen pulang berhasil.';
    }

    /**
     * Validate GPS accuracy to detect fake GPS.
     *
     * Fake GPS apps typically report accuracy of exactly 0 or very low values (< 5m),
     * which is practically impossible for real GPS hardware.
     * Very high accuracy values (> 100m) indicate unreliable positioning.
     */
    private function validateAccuracy(float $accuracy): void
    {
        // Bypass fake GPS check in local development or ngrok, BUT only for desktop browsers
        $host = request()->getHost();
        $isLocalOrNgrok = $host === 'localhost' || 
                          $host === '127.0.0.1' || 
                          str_ends_with($host, '.ngrok-free.dev') || 
                          str_ends_with($host, '.ngrok-free.app') || 
                          str_ends_with($host, '.ngrok.io');

        $userAgent = request()->userAgent() ?? '';
        $isMobile = (bool) preg_match('/(android|iphone|ipad|ipod|webos|blackberry|iemobile|opera mini)/i', $userAgent);

        if (!$isMobile && ($isLocalOrNgrok || app()->environment('local'))) {
            return;
        }

        if ($accuracy < self::MIN_ACCURACY_METERS) {
            throw ValidationException::withMessages([
                'geo' => 'Akurasi GPS mencurigakan (' . round($accuracy, 1) . 'm). Pastikan kamu tidak menggunakan fake GPS.',
            ]);
        }

        if ($accuracy > self::MAX_ACCURACY_METERS) {
            throw ValidationException::withMessages([
                'geo' => 'Akurasi GPS terlalu rendah (' . (int) round($accuracy) . 'm). Pastikan GPS aktif dan kamu berada di area terbuka.',
            ]);
        }
    }
}

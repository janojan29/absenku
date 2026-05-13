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
    public function __construct(private readonly HaversineDistance $distance) {}

    public function checkIn(User $user, float $latitude, float $longitude): string
    {
        $setting = SchoolSetting::singleton();
        $today = Carbon::today();

        $hasApprovedAbsentLeave = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->where('type', 'absent')
            ->where('status', 'approved')
            ->exists();

        if ($hasApprovedAbsentLeave) {
            throw ValidationException::withMessages([
                'check_in' => 'Ijin tidak masuk kamu untuk hari ini sudah disetujui, jadi check-in dinonaktifkan.',
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
        $lateAt = (clone $start)->addMinutes((int) $setting->late_tolerance_minutes);
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
        $lateMinutes = $status === 'late' ? (int) $now->diffInMinutes($start) : null;

        $alreadyCheckedIn = false;

        DB::transaction(function () use ($user, $today, $meters, $status, $lateMinutes, $latitude, $longitude, &$alreadyCheckedIn) {
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
            ]);

            $classRoomId = (int) ($user->studentProfile?->class_room_id ?? 0);
            if ($classRoomId > 0) {
                event(new AttendanceUpdated($attendance->fresh(), $classRoomId));
            }

            if ($status === 'late') {
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

        return $alreadyCheckedIn ? 'Kamu sudah check-in.' : 'Check-in berhasil.';
    }

    public function checkOut(User $user, float $latitude, float $longitude): string
    {
        $setting = SchoolSetting::singleton();
        $today = Carbon::today();

        $hasApprovedAbsentLeave = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->where('type', 'absent')
            ->where('status', 'approved')
            ->exists();

        if ($hasApprovedAbsentLeave) {
            throw ValidationException::withMessages([
                'check_out' => 'Ijin tidak masuk kamu untuk hari ini sudah disetujui, jadi check-out dinonaktifkan.',
            ]);
        }

        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if (! $attendance || $attendance->check_in_at === null) {
            throw ValidationException::withMessages([
                'check_out' => 'Kamu belum check-in hari ini.',
            ]);
        }

        if ($attendance->check_out_at !== null) {
            return 'Kamu sudah check-out.';
        }

        $checkOutStart = Carbon::today()->setTimeFromTimeString($setting->check_out_start_time);
        $checkOutEnd = Carbon::today()->setTimeFromTimeString($setting->check_out_end_time);

        $now = now();
        if ($now->lessThan($checkOutStart)) {
            throw ValidationException::withMessages([
                'check_out' => 'Check-out baru bisa setelah jam ' . substr((string) $setting->check_out_start_time, 0, 5) . '.',
            ]);
        }

        if ($now->greaterThan($checkOutEnd)) {
            throw ValidationException::withMessages([
                'check_out' => 'Waktu check-out sudah berakhir pada jam ' . substr((string) $setting->check_out_end_time, 0, 5) . '.',
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

        DB::transaction(function () use ($attendance, $user, $meters, $latitude, $longitude) {
            $attendance->update([
                'check_out_at' => now(),
                'check_out_late_minutes' => null,
                'check_out_latitude' => $latitude,
                'check_out_longitude' => $longitude,
                'check_out_distance_meters' => (int) round($meters),
            ]);

            $classRoomId = (int) ($user->studentProfile?->class_room_id ?? 0);
            if ($classRoomId > 0) {
                event(new AttendanceUpdated($attendance->fresh(), $classRoomId));
            }

            // Tidak ada notifikasi untuk absen pulang sesuai ketentuan.
        });

        return 'Check-out berhasil.';
    }
}

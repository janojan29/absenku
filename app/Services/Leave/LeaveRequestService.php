<?php

namespace App\Services\Leave;

use App\Events\LeaveRequestCreated;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class LeaveRequestService
{
    public function submit(User $user, string $type, string $reason, string $keterangan, ?string $leaveDate): string
    {
        $today = Carbon::today();
        $targetDate = $type === 'absent'
            ? Carbon::parse((string) $leaveDate)->startOfDay()
            : $today->copy();

        if ($type === 'absent') {
            $isToday = $targetDate->isSameDay($today);
            $isTomorrow = $targetDate->isSameDay($today->copy()->addDay());

            if (! $isToday && ! $isTomorrow) {
                throw ValidationException::withMessages([
                    'leave' => 'Ijin tidak masuk hanya bisa diajukan untuk hari ini atau besok.',
                ]);
            }

            if ($isToday) {
                $todayAttendance = Attendance::query()
                    ->where('user_id', $user->id)
                    ->whereDate('date', $today)
                    ->first();

                if ($todayAttendance && $todayAttendance->check_in_at !== null) {
                    throw ValidationException::withMessages([
                        'leave' => 'Kamu sudah absen masuk hari ini, jadi tidak bisa mengajukan ijin tidak masuk untuk hari ini.',
                    ]);
                }
            }
        }

        if ($type === 'early_leave') {
            $attendance = Attendance::query()
                ->where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();

            if (! $attendance || $attendance->check_in_at === null) {
                throw ValidationException::withMessages([
                    'leave' => 'Ijin pulang lebih awal hanya bisa diajukan setelah absen masuk.',
                ]);
            }
        }

        $existingQuery = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $targetDate);

        if ($type === 'absent') {
            $existingSubmission = $existingQuery->exists();
        } else {
            $existingSubmission = $existingQuery->whereIn('status', ['pending', 'approved'])->exists();
        }

        if ($existingSubmission) {
            throw ValidationException::withMessages([
                'leave' => 'Pengajuan ijin untuk tanggal ' . $targetDate->format('d-m-Y') . ' sudah ada. Dalam 1 hari hanya boleh 1 kali pengajuan ijin.',
            ]);
        }

        $leave = LeaveRequest::query()->create([
            'user_id' => $user->id,
            'date' => $targetDate->toDateString(),
            'type' => $type,
            'reason' => $reason,
            'keterangan' => $keterangan,
            'status' => 'pending',
        ]);

        LeaveRequestCreated::dispatch($leave);

        return 'Pengajuan ijin terkirim (menunggu ACC).';
    }
}

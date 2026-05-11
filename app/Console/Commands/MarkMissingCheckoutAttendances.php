<?php

namespace App\Console\Commands;

use App\Events\AttendanceUpdated;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Attendance;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class MarkMissingCheckoutAttendances extends Command
{
    protected $signature = 'attendance:mark-missing-checkout {--date= : Date in Y-m-d (default: today)}';

    protected $description = 'Mark students as absent when check-in exists but no check-out is recorded.';

    public function handle(): int
    {
        $dateString = $this->option('date') ?: Carbon::today()->toDateString();
        try {
            $date = Carbon::createFromFormat('Y-m-d', $dateString)->startOfDay();
        } catch (\Throwable $e) {
            $this->error("Format tanggal tidak valid. Gunakan format YYYY-MM-DD, contoh: 2026-04-22.");
            return self::FAILURE;
        }

        $updated = 0;

        Attendance::query()
            ->with(['user.studentProfile'])
            ->whereDate('date', $date)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->chunkById(200, function ($attendances) use ($date, &$updated) {
                foreach ($attendances as $attendance) {
                    if ($attendance->status === 'absent') {
                        continue;
                    }

                    $attendance->update([
                        'status' => 'absent',
                    ]);

                    $user = $attendance->user;
                    if (! $user) {
                        continue;
                    }

                    $classRoomId = (int) ($user->studentProfile?->class_room_id ?? 0);
                    if ($classRoomId > 0) {
                        event(new AttendanceUpdated($attendance->fresh(), $classRoomId));
                    }

                    $message = 'Informasi: ' . $user->name . ' belum melakukan absensi pulang pada ' . $date->format('d/m/Y') . '. (Status: ALFA)';

                    $parentWa = $user->studentProfile?->parent_phone_wa ?: $user->studentProfile?->parent_whatsapp_number;
                    if (! empty($parentWa)) {
                        SendWhatsAppMessage::dispatch(
                            to: $parentWa,
                            message: $message,
                            relatedType: Attendance::class,
                            relatedId: $attendance->id,
                        );
                    }

                    $updated++;
                }
            });

        $this->info('Marked missing check-out attendances: ' . $updated);

        return self::SUCCESS;
    }
}

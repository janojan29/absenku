<?php

namespace App\Console\Commands;

use App\Events\AttendanceUpdated;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\StudentProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MarkAbsentAttendances extends Command
{
    protected $signature = 'attendance:mark-absent {--date= : Date in Y-m-d (default: today)}';

    protected $description = 'Mark students as absent for a date when no attendance and no approved leave exists.';

    public function handle(): int
    {
        $dateString = $this->option('date') ?: Carbon::today()->toDateString();
        try {
            $date = Carbon::createFromFormat('Y-m-d', $dateString)->startOfDay();
        } catch (\Throwable $e) {
            $this->error("Format tanggal tidak valid. Gunakan format YYYY-MM-DD, contoh: 2026-04-22.");
            return self::FAILURE;
        }

        /** @var Collection<int,int> $studentUserIds */
        $studentUserIds = StudentProfile::query()->pluck('user_id');

        $alreadyHasAttendance = Attendance::query()
            ->whereDate('date', $date)
            ->whereIn('user_id', $studentUserIds)
            ->pluck('user_id')
            ->all();

        $approvedLeaves = LeaveRequest::query()
            ->whereDate('date', $date)
            ->where('type', 'absent')
            ->where('status', 'approved')
            ->whereIn('user_id', $studentUserIds)
            ->pluck('user_id')
            ->all();

        $skip = array_flip(array_merge($alreadyHasAttendance, $approvedLeaves));

        $created = 0;

        StudentProfile::query()
            ->with(['user'])
            ->chunk(200, function ($profiles) use ($date, $skip, &$created) {
                foreach ($profiles as $sp) {
                    if (isset($skip[$sp->user_id])) {
                        continue;
                    }

                    $attendance = Attendance::query()->create([
                        'user_id' => $sp->user_id,
                        'date' => $date->toDateString(),
                        'status' => 'absent',
                    ]);

                    $created++;

                    event(new AttendanceUpdated($attendance, (int) $sp->class_room_id));

                    $message = 'Informasi: ' . $sp->user->name . ' tidak melakukan absensi masuk pada ' . $date->format('d/m/Y') . '. (Status: ALFA)';

                    $parentWa = $sp->parent_phone_wa ?: $sp->parent_whatsapp_number;
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

        $this->info('Created absent attendances: ' . $created);

        return self::SUCCESS;
    }
}

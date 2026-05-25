<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\LeaveRequest;
use App\Models\SchoolSetting;
use App\Models\StudentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'class_room_id' => ['nullable', 'integer', 'exists:class_rooms,id'],
        ]);

        $classRoomId = $data['class_room_id'] ?? null;
        if ($classRoomId === null) {
            $classRoomId = ClassRoom::query()->orderBy('name')->value('id');
        }

        $setting = SchoolSetting::singleton();
        $today = Carbon::today();

        $studentUserIds = StudentProfile::query()
            ->when($classRoomId, fn ($q) => $q->where('class_room_id', $classRoomId))
            ->pluck('user_id');

        $attendances = Attendance::query()
            ->whereDate('date', $today)
            ->whereIn('user_id', $studentUserIds)
            ->get()
            ->keyBy('user_id');

        $approvedLeaves = LeaveRequest::query()
            ->whereDate('date', $today)
            ->whereIn('user_id', $studentUserIds)
            ->where('status', 'approved')
            ->get()
            ->keyBy('user_id');

        $students = StudentProfile::query()
            ->with(['user', 'classRoom'])
            ->when($classRoomId, fn ($q) => $q->where('class_room_id', $classRoomId))
            ->orderBy('class_room_id')
            ->orderBy('id')
            ->get();

        $lateAt = Carbon::parse($today->toDateString() . ' ' . $setting->check_in_start_time)
            ->addMinutes((int) $setting->late_tolerance_minutes);

        $effectiveStatuses = [];
        foreach ($students as $sp) {
            $attendance = $attendances->get($sp->user_id);
            $leave = $approvedLeaves->get($sp->user_id);

            if ($attendance && $attendance->check_in_at !== null) {
                $checkInAt = Carbon::parse($attendance->check_in_at);
                $effectiveStatuses[$sp->user_id] = $checkInAt->greaterThan($lateAt) ? 'late' : 'present';
            } elseif ($attendance && $attendance->status === 'leave') {
                $effectiveStatuses[$sp->user_id] = 'leave';
            } elseif ($leave) {
                $effectiveStatuses[$sp->user_id] = 'leave';
            } else {
                $effectiveStatuses[$sp->user_id] = 'unknown';
            }
        }

        $counts = [
            'present' => collect($effectiveStatuses)->where(fn ($value) => $value === 'present')->count(),
            'late' => collect($effectiveStatuses)->where(fn ($value) => $value === 'late')->count(),
            'leave' => collect($effectiveStatuses)->where(fn ($value) => $value === 'leave')->count(),
            'unknown' => collect($effectiveStatuses)->where(fn ($value) => $value === 'unknown')->count(),
        ];

        $rows = $students->map(function ($sp) use ($effectiveStatuses) {
            return [
                'name' => $sp->user?->name ?? '-',
                'class_room' => $sp->classRoom?->name ?? '-',
                'jurusan' => $sp->jurusan ?? $sp->classRoom?->jurusan ?? '-',
                'status' => $effectiveStatuses[$sp->user_id] ?? 'unknown',
            ];
        })->values();

        return response()->json([
            'data' => [
                'counts' => $counts,
                'students' => $rows,
                'class_room_id' => $classRoomId,
            ],
        ]);
    }
}

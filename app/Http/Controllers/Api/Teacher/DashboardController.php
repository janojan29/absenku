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
            'class_room_id' => ['nullable', 'string'],
            'search' => ['nullable', 'string'],
        ]);

        $classRoomId = $request->input('class_room_id');
        if ($classRoomId === 'all') {
            $classRoomId = null;
        } elseif ($classRoomId === null && !$request->has('class_room_id')) {
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

        $endCheckIn = Carbon::parse($today->toDateString() . ' ' . $setting->check_in_end_time);
        $lateAt = (clone $endCheckIn)->subMinutes((int) $setting->late_tolerance_minutes);

        $isCheckInClosed = Carbon::now('Asia/Jakarta')->greaterThan($endCheckIn);

        $effectiveStatuses = [];
        $statusLabels = [];
        $keteranganMap = [];
        $isHoliday = \App\Helpers\HolidayHelper::isHoliday($today);

        foreach ($students as $sp) {
            $attendance = $attendances->get($sp->user_id);
            $leave = $approvedLeaves->get($sp->user_id);

            if ($attendance && $attendance->check_in_at !== null) {
                $checkInAt = Carbon::parse($attendance->check_in_at);
                if ($checkInAt->greaterThan($lateAt)) {
                    $effectiveStatuses[$sp->user_id] = 'late';
                    $lateMinutes = $attendance->late_minutes;
                    if (empty($lateMinutes)) {
                        $lateMinutes = (int) $checkInAt->diffInMinutes($lateAt);
                    }
                    $statusLabels[$sp->user_id] = $lateMinutes > 0 ? "Terlambat ({$lateMinutes} Menit)" : "Terlambat";
                } else {
                    $effectiveStatuses[$sp->user_id] = 'present';
                    $statusLabels[$sp->user_id] = 'Hadir';
                }
            } elseif ($attendance && $attendance->status === 'leave') {
                $isSick = ($leave && $leave->reason === 'sick');
                $effectiveStatuses[$sp->user_id] = $isSick ? 'sick' : 'leave';
                $statusLabels[$sp->user_id] = $isSick ? 'Sakit' : 'Izin';
                $keteranganMap[$sp->user_id] = $leave?->keterangan ?? '-';
            } elseif ($attendance && $attendance->status === 'sick') {
                $effectiveStatuses[$sp->user_id] = 'sick';
                $statusLabels[$sp->user_id] = 'Sakit';
                $keteranganMap[$sp->user_id] = $leave?->keterangan ?? '-';
            } elseif ($leave) {
                $isSick = $leave->reason === 'sick';
                $effectiveStatuses[$sp->user_id] = $isSick ? 'sick' : 'leave';
                $statusLabels[$sp->user_id] = $isSick ? 'Sakit' : 'Izin';
                $keteranganMap[$sp->user_id] = $leave->keterangan ?? '-';
            } else {
                if ($isHoliday) {
                    $effectiveStatuses[$sp->user_id] = 'holiday';
                    $statusLabels[$sp->user_id] = 'Libur';
                } elseif ($isCheckInClosed) {
                    $effectiveStatuses[$sp->user_id] = 'absent';
                    $statusLabels[$sp->user_id] = 'Alfa';
                } else {
                    $effectiveStatuses[$sp->user_id] = 'unknown';
                    $statusLabels[$sp->user_id] = 'Belum Absen';
                }
            }
        }

        $counts = [
            'present' => collect($effectiveStatuses)->where(fn ($value) => $value === 'present')->count(),
            'late' => collect($effectiveStatuses)->where(fn ($value) => $value === 'late')->count(),
            'leave' => collect($effectiveStatuses)->where(fn ($value) => in_array($value, ['leave', 'sick']))->count(),
            'unknown' => collect($effectiveStatuses)->where(fn ($value) => in_array($value, ['unknown', 'absent']))->count(),
        ];

        $rows = $students->map(function ($sp) use ($attendances, $effectiveStatuses, $statusLabels, $keteranganMap) {
            $attendance = $attendances->get($sp->user_id);
            return [
                'id' => $sp->id,
                'name' => $sp->user?->name ?? '-',
                'class_room' => $sp->classRoom?->name ?? '-',
                'jurusan' => $sp->jurusan ?? $sp->classRoom?->jurusan ?? '-',
                'status' => $effectiveStatuses[$sp->user_id] ?? 'unknown',
                'status_label' => $statusLabels[$sp->user_id] ?? 'Belum Absen',
                'keterangan' => $keteranganMap[$sp->user_id] ?? '-',
                'check_in_at' => $attendance?->check_in_at ? Carbon::parse($attendance->check_in_at)->format('H:i') : null,
                'check_out_at' => $attendance?->check_out_at ? Carbon::parse($attendance->check_out_at)->format('H:i') : null,
            ];
        })->values();

        $search = $request->input('search');
        if (!empty($search)) {
            $search = strtolower($search);
            $rows = $rows->filter(function ($item) use ($search) {
                return str_contains(strtolower($item['name']), $search);
            })->values();
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;
        $total = $rows->count();
        $paginatedRows = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'data' => [
                'counts' => $counts,
                'students' => $paginatedRows,
                'meta' => [
                    'pagination' => [
                        'current_page' => $page,
                        'last_page' => ceil($total / $perPage),
                        'per_page' => $perPage,
                        'total' => $total,
                    ],
                ],
                'is_check_in_closed' => $isCheckInClosed,
                'class_room_id' => $classRoomId,
                'classrooms' => ClassRoom::query()->orderBy('name')->get()->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'jurusan' => $c->jurusan,
                ])->values(),
            ],
        ]);
    }
}

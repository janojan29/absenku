<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Exports\AttendanceRecapExport;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\LeaveRequest;
use App\Models\SchoolSetting;
use App\Models\StudentProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tab = (string) $request->query('tab', 'detail');
        [$startDate, $endDate, $classRoomId, $status] = $this->filters($request);

        $rows = $this->buildRows($startDate, $endDate, $classRoomId, $status);

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;
        $total = $rows->count();
        $paginatedRows = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'data' => [
                'tab' => $tab,
                'rows' => $paginatedRows,
                'meta' => [
                    'pagination' => [
                        'current_page' => $page,
                        'last_page' => ceil($total / $perPage),
                        'per_page' => $perPage,
                        'total' => $total,
                    ],
                ],
                'filters' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'class_room_id' => $classRoomId,
                    'status' => $status,
                ],
            ],
        ]);
    }

    public function exportExcel(Request $request)
    {
        [$startDate, $endDate, $classRoomId, $status] = $this->filters($request);
        $rows = $this->buildRows($startDate, $endDate, $classRoomId, $status);
        $filename = 'rekap-absensi-' . $startDate->toDateString() . '-sampai-' . $endDate->toDateString() . '.xlsx';

        return Excel::download(new AttendanceRecapExport($rows), $filename);
    }

    public function exportPdf(Request $request)
    {
        [$startDate, $endDate, $classRoomId, $status] = $this->filters($request);
        $rows = $this->buildRows($startDate, $endDate, $classRoomId, $status);

        $pdf = Pdf::loadView('teacher.report-pdf', [
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('rekap-absensi-' . $startDate->toDateString() . '-sampai-' . $endDate->toDateString() . '.pdf');
    }

    private function filters(Request $request): array
    {
        $data = $request->validate([
            'detail_start_date' => ['nullable', 'date'],
            'detail_end_date' => ['nullable', 'date'],
            'class_room_id' => ['nullable', 'integer', 'exists:class_rooms,id'],
            'status' => ['nullable', 'string', 'in:present,late,absent,leave,sick,unknown'],
        ]);

        $startDate = isset($data['detail_start_date'])
            ? Carbon::parse($data['detail_start_date'])->startOfDay()
            : Carbon::today();

        $endDate = isset($data['detail_end_date'])
            ? Carbon::parse($data['detail_end_date'])->startOfDay()
            : $startDate->copy();

        if ($endDate->lt($startDate)) {
            $endDate = $startDate->copy();
        }

        $classRoomId = $data['class_room_id'] ?? null;
        $status = $data['status'] ?? null;

        return [$startDate, $endDate, $classRoomId, $status];
    }

    private function buildRows(Carbon $startDate, Carbon $endDate, ?int $classRoomId, ?string $status): Collection
    {
        $rows = collect();
        for ($cursor = $startDate->copy(); $cursor->lte($endDate); $cursor->addDay()) {
            if (\App\Helpers\HolidayHelper::isHoliday($cursor)) {
                continue;
            }
            $rows = $rows->concat($this->buildRowsForDate($cursor->copy(), $classRoomId));
        }

        if ($status) {
            $statusIndonesianMap = [
                'present' => 'Hadir',
                'late' => 'Terlambat',
                'absent' => 'Alfa',
                'leave' => 'Izin',
                'sick' => 'Sakit',
                'unknown' => 'Belum Absen',
            ];
            $indonesianStatus = $statusIndonesianMap[$status] ?? $status;
            $rows = $rows->where('Status', $indonesianStatus)->values();
        }

        return $rows->values();
    }

    private function buildRowsForDate(Carbon $date, ?int $classRoomId): Collection
    {
        $setting = SchoolSetting::singleton();

        $students = StudentProfile::query()
            ->with(['user', 'classRoom'])
            ->when($classRoomId, fn ($q) => $q->where('class_room_id', $classRoomId))
            ->get();

        $studentUserIds = $students->pluck('user_id');

        $attendances = Attendance::query()
            ->whereDate('date', $date)
            ->whereIn('user_id', $studentUserIds)
            ->get()
            ->keyBy('user_id');

        $leaveRequests = LeaveRequest::query()
            ->whereDate('date', $date)
            ->whereIn('user_id', $studentUserIds)
            ->orderByDesc('created_at')
            ->get()
            ->keyBy('user_id');

        return $students->map(function ($sp) use ($attendances, $leaveRequests, $date, $setting) {
            $attendance = $attendances->get($sp->user_id);
            $leave = $leaveRequests->get($sp->user_id);
            $hasApprovedAbsentLeave = $leave && $leave->status === 'approved' && $leave->type === 'absent';
            $attendanceStatus = null;

            if ($attendance && $attendance->check_in_at !== null) {
                $checkOutEnd = Carbon::parse($date->toDateString() . ' ' . $setting->check_out_end_time);
                $isMissingCheckout = $attendance->check_out_at === null;
                $isPastOrEnded = $date->lt(Carbon::today()) || (now()->greaterThan($checkOutEnd));

                if ($isMissingCheckout && $isPastOrEnded) {
                    $attendanceStatus = 'absent';
                } else {
                    $attendanceStatus = $attendance->status;
                    if (empty($attendanceStatus)) {
                        $endCheckIn = Carbon::parse($date->toDateString() . ' ' . $setting->check_in_end_time);
                        $lateAt = (clone $endCheckIn)->subMinutes((int) $setting->late_tolerance_minutes);
                        $checkInAt = Carbon::parse($attendance->check_in_at);
                        $attendanceStatus = $checkInAt->greaterThan($lateAt) ? 'late' : 'present';
                    }
                }
            } elseif ($attendance) {
                $attendanceStatus = $attendance->status;
            }

            $finalStatus = $attendanceStatus ?? ($hasApprovedAbsentLeave ? ($leave->reason === 'sick' ? 'sick' : 'leave') : 'unknown');

            $statusIndonesianMap = [
                'present' => 'Hadir',
                'late' => 'Terlambat',
                'absent' => 'Alfa',
                'leave' => 'Izin',
                'sick' => 'Sakit',
                'unknown' => 'Belum Absen',
            ];
            $finalStatusIndonesian = $statusIndonesianMap[$finalStatus] ?? $finalStatus;
            if ($finalStatus === 'late') {
                $lateMinutes = $attendance?->late_minutes;
                if (empty($lateMinutes) && $attendance?->check_in_at) {
                    $endCheckIn = Carbon::parse($date->toDateString() . ' ' . $setting->check_in_end_time);
                    $lateAt = (clone $endCheckIn)->subMinutes((int) $setting->late_tolerance_minutes);
                    $checkInAt = Carbon::parse($attendance->check_in_at);
                    $lateMinutes = (int) $checkInAt->diffInMinutes($lateAt);
                }
                if ($lateMinutes > 0) {
                    $finalStatusIndonesian = "Terlambat ({$lateMinutes} Menit)";
                }
            }

            $statusIzinIndonesianMap = [
                'pending' => 'Menunggu',
                'approved' => 'Disetujui',
                'rejected' => 'Ditolak',
            ];
            $statusIzinIndonesian = $leave ? ($statusIzinIndonesianMap[$leave->status] ?? $leave->status) : '-';

            $jenisIzin = '-';
            if ($leave) {
                $jenisIzin = $leave->type === 'absent' ? 'Izin Tidak Masuk' : 'Izin Pulang Lebih Awal';
            }

            $alasanIzin = '-';
            if ($leave) {
                $alasanIzin = $leave->reason === 'urgent'
                    ? 'Urusan Penting/Mendadak'
                    : ($leave->reason === 'sick' ? 'Sakit' : $leave->reason);
            }

            $waktuTidakMasuk = '-';
            if ($leave && $leave->type === 'absent') {
                $requestedAt = Carbon::parse($leave->created_at)->startOfDay();
                $leaveAt = Carbon::parse($leave->date)->startOfDay();

                if ($leaveAt->isSameDay($requestedAt)) {
                    $waktuTidakMasuk = 'Hari Ini';
                } elseif ($leaveAt->isSameDay($requestedAt->copy()->addDay())) {
                    $waktuTidakMasuk = 'Besok';
                } else {
                    $waktuTidakMasuk = $leaveAt->format('d-m-Y');
                }
            }

            return [
                'Tanggal' => $date->toDateString(),
                'Kelas' => $sp->classRoom?->name ?? '-',
                'Jurusan' => $sp->jurusan ?? $sp->classRoom?->jurusan ?? '-',
                'Nama' => $sp->user->name,
                'Status' => $finalStatusIndonesian,
                'Status Izin' => $statusIzinIndonesian,
                'Jenis Izin' => $jenisIzin,
                'Alasan Izin' => $alasanIzin,
                'Waktu Tidak Masuk' => $waktuTidakMasuk,
                'Keterangan Izin' => $leave?->keterangan ?? '-',
                'Masuk' => optional($attendance?->check_in_at)->format('H:i') ?? '-',
                'Pulang' => optional($attendance?->check_out_at)->format('H:i') ?? '-',
            ];
        })->values();
    }

    public function summary(Request $request): JsonResponse
    {
        [$summaryRows, $summaryFilter] = $this->buildSummaryRecap($request);

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;
        
        // $summaryRows is a Collection returned by buildSummaryRecap
        $total = $summaryRows->count();
        $paginatedRows = $summaryRows->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'data' => [
                'rows' => $paginatedRows,
                'meta' => [
                    'pagination' => [
                        'current_page' => $page,
                        'last_page' => ceil($total / $perPage),
                        'per_page' => $perPage,
                        'total' => $total,
                    ],
                ],
                'filters' => $summaryFilter,
            ],
        ]);
    }

    public function exportSummaryExcel(Request $request)
    {
        [$summaryRows, $summaryFilter] = $this->buildSummaryRecap($request);

        $startDate = Carbon::parse($summaryFilter['resolved_start'] ?? now()->toDateString());
        $endDate = Carbon::parse($summaryFilter['resolved_end'] ?? now()->toDateString());

        $filename = 'rekap-keterangan-' . $startDate->toDateString() . '-sampai-' . $endDate->toDateString() . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\SummaryRecapExport($summaryRows), $filename);
    }

    public function exportSummaryPdf(Request $request)
    {
        [$summaryRows, $summaryFilter] = $this->buildSummaryRecap($request);

        $startDate = Carbon::parse($summaryFilter['resolved_start'] ?? now()->toDateString());
        $endDate = Carbon::parse($summaryFilter['resolved_end'] ?? now()->toDateString());

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('teacher.summary-recap-pdf', [
            'summaryRows' => $summaryRows,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('rekap-keterangan-' . $startDate->toDateString() . '-sampai-' . $endDate->toDateString() . '.pdf');
    }

    private function buildSummaryRecap(Request $request, $students = null): array
    {
        $setting = \App\Models\SchoolSetting::singleton();
        $period = (string) $request->query('summary_period', 'range');
        $today = Carbon::today();

        $summaryFilter = [
            'period' => $period,
            'start_date' => (string) $request->query('start_date', $today->toDateString()),
            'end_date' => (string) $request->query('end_date', $today->toDateString()),
            'class_room_id' => $request->filled('class_room_id')
                ? (int) $request->query('class_room_id')
                : null,
        ];

        try {
            $start = Carbon::parse($summaryFilter['start_date'])->startOfDay();
            $end = Carbon::parse($summaryFilter['end_date'])->startOfDay();
        } catch (\Throwable) {
            $start = $today->copy();
            $end = $today->copy();
        }
        if ($end->lt($start)) {
            $end = $start->copy();
        }

        $summaryFilter['resolved_start'] = $start->toDateString();
        $summaryFilter['resolved_end'] = $end->toDateString();

        $classes = \App\Models\ClassRoom::query()->orderBy('name')->get()->keyBy('id');
        if ($summaryFilter['class_room_id'] !== null && ! $classes->has($summaryFilter['class_room_id'])) {
            $summaryFilter['class_room_id'] = null;
        }

        if ($students === null) {
            $students = \App\Models\StudentProfile::query()
                ->with(['user', 'classRoom'])
                ->whereNotNull('class_room_id')
                ->when($summaryFilter['class_room_id'], fn($q, $classId) => $q->where('class_room_id', $classId))
                ->join('users', 'student_profiles.user_id', '=', 'users.id')
                ->orderBy('users.name')
                ->select('student_profiles.*')
                ->get();
        }

        $studentUserIds = $students->pluck('user_id')->values();

        $attendances = \App\Models\Attendance::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('user_id', $studentUserIds)
            ->get(['user_id', 'date', 'status', 'check_in_at', 'check_out_at']);

        $approvedAbsentLeaves = \App\Models\LeaveRequest::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('user_id', $studentUserIds)
            ->where('status', 'approved')
            ->where('type', 'absent')
            ->get(['user_id', 'date']);

        $attendanceMap = [];
        foreach ($attendances as $attendance) {
            $dateKey = Carbon::parse($attendance->date)->toDateString();
            $status = null;

            if ($attendance->check_in_at !== null) {
                $checkOutEnd = Carbon::parse($dateKey . ' ' . $setting->check_out_end_time);
                $isMissingCheckout = $attendance->check_out_at === null;
                $isPastOrEnded = Carbon::parse($dateKey)->lt(Carbon::today()) || (now()->greaterThan($checkOutEnd));

                if ($isMissingCheckout && $isPastOrEnded) {
                    $status = 'absent';
                } else {
                    $status = $attendance->status;
                    if (empty($status)) {
                        $endCheckIn = Carbon::parse($dateKey . ' ' . $setting->check_in_end_time);
                        $lateAt = (clone $endCheckIn)->subMinutes((int) $setting->late_tolerance_minutes);
                        $checkInAt = Carbon::parse($attendance->check_in_at);
                        $status = $checkInAt->greaterThan($lateAt) ? 'late' : 'present';
                    }
                }
            }

            $attendanceMap[$attendance->user_id][$dateKey] = $status;
        }

        $leaveMap = [];
        foreach ($approvedAbsentLeaves as $leave) {
            $dateKey = Carbon::parse($leave->date)->toDateString();
            $leaveMap[$leave->user_id][$dateKey] = true;
        }

        $dates = [];
        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            if (!\App\Helpers\HolidayHelper::isHoliday($cursor)) {
                $dates[] = $cursor->toDateString();
            }
        }

        $summaryRows = $students->map(function ($student) use ($attendanceMap, $leaveMap, $dates, $classes) {
            $hadir = 0;
            $izin = 0;
            $telat = 0;
            $alfa = 0;

            foreach ($dates as $dateKey) {
                $status = $attendanceMap[$student->user_id][$dateKey] ?? null;
                $hasLeave = isset($leaveMap[$student->user_id][$dateKey]);

                if ($status === 'present') {
                    $hadir++;
                } elseif ($status === 'late') {
                    $telat++;
                } elseif ($status === 'leave' || $hasLeave) {
                    $izin++;
                } else {
                    $alfa++;
                }
            }

            $class = $classes->get($student->class_room_id);

            return [
                'Nama' => $student->user?->name ?? '-',
                'Kelas' => $class?->name ?? '-',
                'Jurusan' => $student->jurusan ?? $class?->jurusan ?? '-',
                'Hadir' => $hadir,
                'Izin' => $izin,
                'Telat' => $telat,
                'Alfa' => $alfa,
            ];
        })->sortBy('Nama')->values();

        return [$summaryRows, $summaryFilter];
    }
}

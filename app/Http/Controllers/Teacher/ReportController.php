<?php

namespace App\Http\Controllers\Teacher;

use App\Exports\AttendanceRecapExport;
use App\Exports\SummaryRecapExport;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\LeaveRequest;
use App\Models\SchoolSetting;
use App\Models\StudentProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'detail');
        [$startDate, $endDate, $classRoomId, $status] = $this->filters($request);

        $classes = ClassRoom::query()->orderBy('name')->get();

        // Get class filter based on active tab
        $currentClassRoomId = null;
        if ($tab === 'summary') {
            $currentClassRoomId = $request->filled('summary_class_room_id') ? (int) $request->query('summary_class_room_id') : null;
        } else {
            $currentClassRoomId = $request->filled('class_room_id') ? (int) $request->query('class_room_id') : null;
        }

        // Paginate students with 20 results per page, sorted by user's name
        $studentsPaginator = StudentProfile::query()
            ->with(['user', 'classRoom'])
            ->whereNotNull('class_room_id')
            ->when($currentClassRoomId, fn($q) => $q->where('class_room_id', $currentClassRoomId))
            ->join('users', 'student_profiles.user_id', '=', 'users.id')
            ->orderBy('users.name')
            ->select('student_profiles.*')
            ->paginate(20)
            ->withQueryString();

        $paginatedStudents = $studentsPaginator->getCollection();

        $rows = $this->buildRows($startDate, $endDate, $classRoomId, $status, $paginatedStudents);
        $summaryRows = collect();
        $summaryFilter = [];

        if ($tab === 'summary') {
            [$summaryRows, $summaryFilter] = $this->buildSummaryRecap($request, $paginatedStudents);
        }

        return view('teacher.report', [
            'tab' => $tab,
            'classes' => $classes,
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'classRoomId' => $classRoomId,
            'status' => $status,
            'summaryRows' => $summaryRows,
            'summaryFilter' => $summaryFilter,
            'studentsPaginator' => $studentsPaginator,
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

    public function exportSummaryExcel(Request $request)
    {
        [$summaryRows, $summaryFilter] = $this->buildSummaryRecap($request);

        $startDate = Carbon::parse($summaryFilter['resolved_start'] ?? now()->toDateString());
        $endDate = Carbon::parse($summaryFilter['resolved_end'] ?? now()->toDateString());

        $filename = 'rekap-keterangan-' . $startDate->toDateString() . '-sampai-' . $endDate->toDateString() . '.xlsx';

        return Excel::download(new SummaryRecapExport($summaryRows), $filename);
    }

    public function exportSummaryPdf(Request $request)
    {
        [$summaryRows, $summaryFilter] = $this->buildSummaryRecap($request);

        $startDate = Carbon::parse($summaryFilter['resolved_start'] ?? now()->toDateString());
        $endDate = Carbon::parse($summaryFilter['resolved_end'] ?? now()->toDateString());

        $pdf = Pdf::loadView('teacher.summary-recap-pdf', [
            'summaryRows' => $summaryRows,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('rekap-keterangan-' . $startDate->toDateString() . '-sampai-' . $endDate->toDateString() . '.pdf');
    }

    private function filters(Request $request): array
    {
        $data = $request->validate([
            'detail_start_date' => ['nullable', 'date'],
            'detail_end_date' => ['nullable', 'date'],
            'class_room_id' => ['nullable', 'integer', 'exists:class_rooms,id'],
            'status' => ['nullable', 'string', 'in:present,late,absent,leave,unknown'],
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

    private function buildRows(Carbon $startDate, Carbon $endDate, ?int $classRoomId, ?string $status, $students = null): Collection
    {
        $rows = collect();

        for ($cursor = $startDate->copy(); $cursor->lte($endDate); $cursor->addDay()) {
            $rows = $rows->concat($this->buildRowsForDate($cursor->copy(), $classRoomId, $students));
        }

        if ($status) {
            $rows = $rows->where('Status', $status)->values();
        }

        return $rows->values();
    }

    private function buildRowsForDate(Carbon $date, ?int $classRoomId, $students = null): Collection
    {
        $setting = SchoolSetting::singleton();

        if ($students === null) {
            $students = StudentProfile::query()
                ->with(['user', 'classRoom'])
                ->whereNotNull('class_room_id')
                ->when($classRoomId, fn($q) => $q->where('class_room_id', $classRoomId))
                ->join('users', 'student_profiles.user_id', '=', 'users.id')
                ->orderBy('users.name')
                ->select('student_profiles.*')
                ->get();
        }

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

        $rows = $students->map(function ($sp) use ($attendances, $leaveRequests, $date, $setting) {
            $attendance = $attendances->get($sp->user_id);
            $leave = $leaveRequests->get($sp->user_id);
            $hasApprovedAbsentLeave = $leave && $leave->status === 'approved' && $leave->type === 'absent';
            $attendanceStatus = null;

            if ($attendance && $attendance->check_out_at !== null) {
                $lateAt = Carbon::parse($date->toDateString() . ' ' . $setting->check_in_start_time)
                    ->addMinutes((int) $setting->late_tolerance_minutes);
                $checkInAt = Carbon::parse($attendance->check_in_at);

                $attendanceStatus = $checkInAt->greaterThan($lateAt) ? 'late' : 'present';
            } elseif ($attendance && $attendance->check_in_at !== null && $attendance->check_out_at === null) {
                $attendanceStatus = 'absent';
            }

            $finalStatus = $attendanceStatus ?? ($hasApprovedAbsentLeave ? 'leave' : 'unknown');

            $jenisIjin = '-';
            if ($leave) {
                $jenisIjin = $leave->type === 'absent' ? 'Ijin Tidak Masuk' : 'Ijin Pulang Lebih Awal';
            }

            $alasanIjin = '-';
            if ($leave) {
                $alasanIjin = $leave->reason === 'urgent' ? 'Urusan Penting/Mendadak' : ($leave->reason === 'sick' ? 'Sakit' : $leave->reason);
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
                'Status' => $finalStatus,
                'Status Ijin' => $leave?->status ?? '-',
                'Jenis Ijin' => $jenisIjin,
                'Alasan Ijin' => $alasanIjin,
                'Waktu Tidak Masuk' => $waktuTidakMasuk,
                'Keterangan Ijin' => $leave?->keterangan ?? '-',
                'Masuk' => optional($attendance?->check_in_at)->format('H:i') ?? '-',
                'Pulang' => optional($attendance?->check_out_at)->format('H:i') ?? '-',
            ];
        });

        return $rows->values();
    }

    private function buildSummaryRecap(Request $request, $students = null): array
    {
        $setting = SchoolSetting::singleton();
        $period = (string) $request->query('summary_period', 'range');
        $today = Carbon::today();

        $summaryFilter = [
            'period' => $period,
            'start_date' => (string) $request->query('summary_start_date', $today->toDateString()),
            'end_date' => (string) $request->query('summary_end_date', $today->toDateString()),
            'week' => (string) $request->query('summary_week', $today->format('o-\\WW')),
            'month' => (string) $request->query('summary_month', $today->format('Y-m')),
            'class_room_id' => $request->filled('summary_class_room_id')
                ? (int) $request->query('summary_class_room_id')
                : null,
        ];

        if ($period === 'week') {
            if (preg_match('/^(\\d{4})-W(\\d{2})$/', $summaryFilter['week'], $matches) === 1) {
                $start = Carbon::now()->setISODate((int) $matches[1], (int) $matches[2], 1)->startOfDay();
                $end = $start->copy()->endOfWeek();
            } else {
                $start = $today->copy()->startOfWeek();
                $end = $today->copy()->endOfWeek();
            }
        } elseif ($period === 'month') {
            try {
                $start = Carbon::createFromFormat('Y-m', $summaryFilter['month'])->startOfMonth();
                $end = $start->copy()->endOfMonth();
            } catch (\Throwable) {
                $start = $today->copy()->startOfMonth();
                $end = $today->copy()->endOfMonth();
            }
        } else {
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
        }

        $summaryFilter['resolved_start'] = $start->toDateString();
        $summaryFilter['resolved_end'] = $end->toDateString();

        $classes = ClassRoom::query()->orderBy('name')->get()->keyBy('id');
        if ($summaryFilter['class_room_id'] !== null && ! $classes->has($summaryFilter['class_room_id'])) {
            $summaryFilter['class_room_id'] = null;
        }

        if ($students === null) {
            $students = StudentProfile::query()
                ->with(['user', 'classRoom'])
                ->whereNotNull('class_room_id')
                ->when($summaryFilter['class_room_id'], fn($q, $classId) => $q->where('class_room_id', $classId))
                ->join('users', 'student_profiles.user_id', '=', 'users.id')
                ->orderBy('users.name')
                ->select('student_profiles.*')
                ->get();
        }

        $studentUserIds = $students->pluck('user_id')->values();

        $attendances = Attendance::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('user_id', $studentUserIds)
            ->get(['user_id', 'date', 'status', 'check_in_at', 'check_out_at']);

        $approvedAbsentLeaves = LeaveRequest::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('user_id', $studentUserIds)
            ->where('status', 'approved')
            ->where('type', 'absent')
            ->get(['user_id', 'date']);

        $attendanceMap = [];
        foreach ($attendances as $attendance) {
            $dateKey = Carbon::parse($attendance->date)->toDateString();
            $status = null;

            if ($attendance->check_out_at !== null) {
                $lateAt = Carbon::parse($dateKey . ' ' . $setting->check_in_start_time)
                    ->addMinutes((int) $setting->late_tolerance_minutes);
                $checkInAt = Carbon::parse($attendance->check_in_at);

                $status = $checkInAt->greaterThan($lateAt) ? 'late' : 'present';
            } elseif ($attendance->check_in_at !== null && $attendance->check_out_at === null) {
                $status = 'absent';
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
            $dates[] = $cursor->toDateString();
        }

        $summaryRows = $students->map(function ($student) use ($attendanceMap, $leaveMap, $dates, $classes) {
            $hadir = 0;
            $ijin = 0;
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
                    $ijin++;
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
                'Ijin' => $ijin,
                'Telat' => $telat,
                'Alfa' => $alfa,
            ];
        })->sortBy('Nama')->values();

        return [$summaryRows, $summaryFilter];
    }
}

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

        return response()->json([
            'data' => [
                'tab' => $tab,
                'rows' => $rows->values(),
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

    private function buildRows(Carbon $startDate, Carbon $endDate, ?int $classRoomId, ?string $status): Collection
    {
        $rows = collect();
        for ($cursor = $startDate->copy(); $cursor->lte($endDate); $cursor->addDay()) {
            $rows = $rows->concat($this->buildRowsForDate($cursor->copy(), $classRoomId));
        }

        if ($status) {
            $rows = $rows->where('Status', $status)->values();
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
                $alasanIjin = $leave->reason === 'urgent'
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
                'Status' => $finalStatus,
                'Status Ijin' => $leave?->status ?? '-',
                'Jenis Ijin' => $jenisIjin,
                'Alasan Ijin' => $alasanIjin,
                'Waktu Tidak Masuk' => $waktuTidakMasuk,
                'Keterangan Ijin' => $leave?->keterangan ?? '-',
                'Masuk' => optional($attendance?->check_in_at)->format('H:i') ?? '-',
                'Pulang' => optional($attendance?->check_out_at)->format('H:i') ?? '-',
            ];
        })->values();
    }
}

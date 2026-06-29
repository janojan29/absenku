<?php

namespace App\Http\Controllers\Api\Picket;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class LeaveQueueController extends Controller
{
    private function getHistoryQuery(Request $request)
    {
        $historyQuery = LeaveRequest::query()
            ->with(['user.studentProfile.classRoom', 'decidedBy'])
            ->whereIn('status', ['approved', 'rejected']);

        if ($request->has('search') && $request->search) {
            $historyQuery->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('status') && $request->status) {
            $historyQuery->where('status', $request->status);
        }

        if ($request->has('type') && $request->type) {
            $historyQuery->where('type', $request->type);
        }

        if ($request->has('date_from') && $request->date_from) {
            $historyQuery->whereDate('date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $historyQuery->whereDate('date', '<=', $request->date_to);
        }

        return $historyQuery->orderByDesc('decided_at')->orderByDesc('updated_at');
    }
    public function index(Request $request): JsonResponse
    {
        $pending = LeaveRequest::query()
            ->with(['user.studentProfile.classRoom'])
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->paginate(15, ['*'], 'pendingPage');

        $history = $this->getHistoryQuery($request)->paginate(15, ['*'], 'historyPage');

        return response()->json([
            'pending' => [
                'data' => $pending->getCollection()
                    ->map(fn ($item) => (new LeaveRequestResource($item))->toArray($request))
                    ->values(),
                'meta' => [
                    'pagination' => [
                        'current_page' => $pending->currentPage(),
                        'last_page' => $pending->lastPage(),
                        'per_page' => $pending->perPage(),
                        'total' => $pending->total(),
                    ],
                ],
            ],
            'history' => [
                'data' => $history->getCollection()
                    ->map(fn ($item) => (new LeaveRequestResource($item))->toArray($request))
                    ->values(),
                'meta' => [
                    'pagination' => [
                        'current_page' => $history->currentPage(),
                        'last_page' => $history->lastPage(),
                        'per_page' => $history->perPage(),
                        'total' => $history->total(),
                    ],
                ],
            ],
        ]);
    }

    public function exportExcel(Request $request)
    {
        $histories = $this->getHistoryQuery($request)->get();
        
        $rows = $histories->map(function ($leave, $index) {
            return [
                'No' => $index + 1,
                'Tanggal' => $leave->date->format('d/m/Y'),
                'Siswa' => $leave->user->name,
                'Kelas' => $leave->user->studentProfile?->classRoom?->name ?? '-',
                'Jenis' => $leave->type === 'absent' ? 'Tidak Masuk' : 'Pulang Awal',
                'Alasan' => $leave->reason === 'urgent' ? 'Urusan Penting/Mendadak' : ($leave->reason === 'sick' ? 'Sakit' : ($leave->reason ?? '-')),
                'Keterangan' => $leave->keterangan ?? '-',
                'Status' => $leave->status === 'approved' ? 'Disetujui' : 'Ditolak',
                'Diproses Pada' => $leave->decided_at?->format('d/m/Y H:i') ?? '-',
                'Petugas Piket' => $leave->decidedBy?->name ?? '-',
            ];
        });

        $filename = 'riwayat-izin-' . now()->format('Ymd-His') . '.xlsx';
        return Excel::download(new \App\Exports\LeaveHistoryExport($rows), $filename);
    }

    public function exportPdf(Request $request)
    {
        $histories = $this->getHistoryQuery($request)->get();
        
        $rows = $histories->map(function ($leave) {
            return [
                'date' => $leave->date->format('d/m/Y'),
                'student' => $leave->user->name,
                'class' => $leave->user->studentProfile?->classRoom?->name ?? '-',
                'type' => $leave->type === 'absent' ? 'Tidak Masuk' : 'Pulang Awal',
                'reason' => $leave->reason === 'urgent' ? 'Urusan Penting/Mendadak' : ($leave->reason === 'sick' ? 'Sakit' : ($leave->reason ?? '-')),
                'keterangan' => $leave->keterangan ?? '-',
                'status' => $leave->status,
                'picket' => $leave->decidedBy?->name ?? '-',
            ];
        });

        $pdf = Pdf::loadView('picket.leave-history-pdf', ['rows' => $rows]);
        $filename = 'riwayat-izin-' . now()->format('Ymd-His') . '.pdf';
        
        return $pdf->download($filename);
    }
}

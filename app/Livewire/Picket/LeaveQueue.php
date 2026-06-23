<?php

namespace App\Livewire\Picket;

use App\Models\LeaveRequest;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class LeaveQueue extends Component
{
    use WithPagination;

    public function paginationView()
    {
        return 'vendor.livewire.custom-tailwind';
    }

    public string $search = '';
    public string $filterStatus = '';
    public string $filterType = '';
    public string $filterDateFrom = '';
    public string $filterDateTo = '';

    public function updatingSearch()
    {
        $this->resetPage('historyPage');
    }

    public function updatingFilterStatus()
    {
        $this->resetPage('historyPage');
    }

    public function updatingFilterType()
    {
        $this->resetPage('historyPage');
    }

    public function updatingFilterDateFrom()
    {
        $this->resetPage('historyPage');
    }

    public function updatingFilterDateTo()
    {
        $this->resetPage('historyPage');
    }

    public function resetFilters()
    {
        $this->reset(['search', 'filterStatus', 'filterType', 'filterDateFrom', 'filterDateTo']);
        $this->resetPage('historyPage');
    }

    private function getHistoryQuery()
    {
        $historyQuery = LeaveRequest::query()
            ->with(['user.studentProfile.classRoom', 'decidedBy'])
            ->whereIn('status', ['approved', 'rejected']);

        if ($this->search) {
            $historyQuery->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterStatus) {
            $historyQuery->where('status', $this->filterStatus);
        }

        if ($this->filterType) {
            $historyQuery->where('type', $this->filterType);
        }

        if ($this->filterDateFrom) {
            $historyQuery->whereDate('date', '>=', $this->filterDateFrom);
        }

        if ($this->filterDateTo) {
            $historyQuery->whereDate('date', '<=', $this->filterDateTo);
        }

        return $historyQuery->orderByDesc('decided_at')->orderByDesc('updated_at');
    }

    public function exportExcel()
    {
        $histories = $this->getHistoryQuery()->get();
        
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

    public function exportPdf()
    {
        $histories = $this->getHistoryQuery()->get();
        
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
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    public function render()
    {
        $leaveRequests = LeaveRequest::query()
            ->with(['user.studentProfile.classRoom'])
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->paginate(15, ['*'], 'pendingPage');

        $leaveHistories = $this->getHistoryQuery()->paginate(15, ['*'], 'historyPage');

        return view('livewire.picket.leave-queue', [
            'leaveRequests' => $leaveRequests,
            'leaveHistories' => $leaveHistories,
        ]);
    }
}

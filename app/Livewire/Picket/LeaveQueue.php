<?php

namespace App\Livewire\Picket;

use App\Models\LeaveRequest;
use Livewire\Component;
use Livewire\WithPagination;

class LeaveQueue extends Component
{
    use WithPagination;

    public function render()
    {
        $leaveRequests = LeaveRequest::query()
            ->with(['user.studentProfile.classRoom'])
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->paginate(15, ['*'], 'pendingPage');

        $leaveHistories = LeaveRequest::query()
            ->with(['user.studentProfile.classRoom', 'decidedBy'])
            ->whereIn('status', ['approved', 'rejected'])
            ->orderByDesc('decided_at')
            ->orderByDesc('updated_at')
            ->paginate(15, ['*'], 'historyPage');

        return view('livewire.picket.leave-queue', [
            'leaveRequests' => $leaveRequests,
            'leaveHistories' => $leaveHistories,
        ]);
    }
}

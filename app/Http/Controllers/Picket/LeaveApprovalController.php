<?php

namespace App\Http\Controllers\Picket;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveRequest\LeaveApprovalRequest;
use App\Models\LeaveRequest;
use App\Services\Leave\LeaveApprovalService;
use Illuminate\Http\RedirectResponse;

class LeaveApprovalController extends Controller
{
    public function approve(LeaveApprovalRequest $request, LeaveRequest $leaveRequest, LeaveApprovalService $service): RedirectResponse
    {
        $message = $service->approve(
            $request->user(),
            $leaveRequest,
            $request->validated('decision_note'),
        );

        return back()->with('status', $message);
    }

    public function reject(LeaveApprovalRequest $request, LeaveRequest $leaveRequest, LeaveApprovalService $service): RedirectResponse
    {
        $message = $service->reject(
            $request->user(),
            $leaveRequest,
            $request->validated('decision_note'),
        );

        return back()->with('status', $message);
    }
}

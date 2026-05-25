<?php

namespace App\Http\Controllers\Api\Picket;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveRequest\LeaveApprovalRequest;
use App\Models\LeaveRequest;
use App\Services\Leave\LeaveApprovalService;
use Illuminate\Http\JsonResponse;

class LeaveApprovalController extends Controller
{
    public function approve(LeaveApprovalRequest $request, LeaveRequest $leaveRequest, LeaveApprovalService $service): JsonResponse
    {
        $message = $service->approve(
            $request->user(),
            $leaveRequest,
            $request->validated('decision_note'),
        );

        return response()->json(['message' => $message]);
    }

    public function reject(LeaveApprovalRequest $request, LeaveRequest $leaveRequest, LeaveApprovalService $service): JsonResponse
    {
        $message = $service->reject(
            $request->user(),
            $leaveRequest,
            $request->validated('decision_note'),
        );

        return response()->json(['message' => $message]);
    }
}

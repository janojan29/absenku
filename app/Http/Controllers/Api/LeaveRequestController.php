<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveRequest\StoreLeaveRequest;
use App\Services\Leave\LeaveRequestService;
use Illuminate\Http\JsonResponse;

class LeaveRequestController extends Controller
{
    public function store(StoreLeaveRequest $request, LeaveRequestService $service): JsonResponse
    {
        $message = $service->submit(
            $request->user(),
            (string) $request->validated('type'),
            (string) $request->validated('reason'),
            (string) $request->validated('keterangan'),
            $request->validated('leave_date'),
        );

        return response()->json(['message' => $message]);
    }
}

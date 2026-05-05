<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveRequest\StoreLeaveRequest;
use App\Services\Leave\LeaveRequestService;
use Illuminate\Http\RedirectResponse;

class LeaveRequestController extends Controller
{
    public function store(StoreLeaveRequest $request, LeaveRequestService $service): RedirectResponse
    {
        $message = $service->submit(
            $request->user(),
            (string) $request->validated('type'),
            (string) $request->validated('reason'),
            (string) $request->validated('keterangan'),
            $request->validated('leave_date'),
        );

        return back()->with('status', $message);
    }
}

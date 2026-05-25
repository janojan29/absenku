<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\LeaveRequestResource;
use App\Http\Resources\SchoolSettingResource;
use App\Http\Requests\Attendance\CheckInRequest;
use App\Http\Requests\Attendance\CheckOutRequest;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\SchoolSetting;
use App\Services\Attendance\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $today = Carbon::today();

        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $recent = Attendance::query()
            ->where('user_id', $user->id)
            ->orderByDesc('date')
            ->limit(14)
            ->get();

        $setting = SchoolSetting::singleton();

        $todayLeaveSubmission = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->orderByDesc('created_at')
            ->first();

        $hasApprovedAbsentLeaveToday = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->where('type', 'absent')
            ->where('status', 'approved')
            ->exists();

        $leaveDatesWithSubmission = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$today->toDateString(), $today->copy()->addDay()->toDateString()])
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->values()
            ->all();

        $checkInStart = Carbon::today()->setTimeFromTimeString($setting->check_in_start_time);
        $checkInEnd = Carbon::today()->setTimeFromTimeString($setting->check_in_end_time);
        $checkOutStart = Carbon::today()->setTimeFromTimeString($setting->check_out_start_time);
        $checkOutEnd = Carbon::today()->setTimeFromTimeString($setting->check_out_end_time);
        $now = now();

        $hasReachedCheckInStart = $now->greaterThanOrEqualTo($checkInStart);
        $isAfterCheckInEnd = $now->greaterThan($checkInEnd);
        $canCheckInNow = $hasReachedCheckInStart && ! $isAfterCheckInEnd;

        $hasReachedCheckOutStart = $now->greaterThanOrEqualTo($checkOutStart);
        $isAfterCheckOutEnd = $now->greaterThan($checkOutEnd);
        $canCheckOutNow = $hasReachedCheckOutStart && ! $isAfterCheckOutEnd;

        if ($hasApprovedAbsentLeaveToday) {
            $canCheckInNow = false;
            $canCheckOutNow = false;
        }

        $showLeaveForm = $todayLeaveSubmission === null || $hasReachedCheckOutStart;

        return response()->json([
            'data' => [
                'attendance' => $attendance ? (new AttendanceResource($attendance))->toArray($request) : null,
                'recent' => $recent->map(fn ($item) => (new AttendanceResource($item))->toArray($request))->values(),
                'setting' => (new SchoolSettingResource($setting))->toArray($request),
                'can_check_in_now' => $canCheckInNow,
                'has_reached_check_in_start' => $hasReachedCheckInStart,
                'is_after_check_in_end' => $isAfterCheckInEnd,
                'can_check_out_now' => $canCheckOutNow,
                'is_after_check_out_end' => $isAfterCheckOutEnd,
                'today_leave_submission' => $todayLeaveSubmission ? (new LeaveRequestResource($todayLeaveSubmission))->toArray($request) : null,
                'has_approved_absent_leave_today' => $hasApprovedAbsentLeaveToday,
                'show_leave_form' => $showLeaveForm,
                'leave_dates_with_submission' => $leaveDatesWithSubmission,
            ],
        ]);
    }

    public function checkIn(CheckInRequest $request, AttendanceService $service): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $message = $service->checkIn(
            $user,
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
        );

        return response()->json(['message' => $message]);
    }

    public function checkOut(CheckOutRequest $request, AttendanceService $service): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $message = $service->checkOut(
            $user,
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
        );

        return response()->json(['message' => $message]);
    }
}

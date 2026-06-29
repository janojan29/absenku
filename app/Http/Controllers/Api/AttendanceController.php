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
            ->get()
            ->filter(fn ($item) => !\App\Helpers\HolidayHelper::isHoliday($item->date))
            ->take(7)
            ->values();

        $setting = SchoolSetting::singleton();

        $todayLeaveSubmission = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where(function($query) use ($today) {
                $query->whereDate('date', $today)
                      ->orWhereDate('date', $today->copy()->addDay())
                      ->orWhereDate('decided_at', $today);
            })
            ->orderByDesc('created_at')
            ->first();

        if ($todayLeaveSubmission && $todayLeaveSubmission->status === 'rejected') {
            $isDecidedToday = $todayLeaveSubmission->decided_at && $todayLeaveSubmission->decided_at->isToday();
            if (!$isDecidedToday) {
                $todayLeaveSubmission = null;
            }
        }

        $hasApprovedAbsentLeaveToday = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->where('type', 'absent')
            ->where('status', 'approved')
            ->exists();

        $absentBlockedDates = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$today->toDateString(), $today->copy()->addDay()->toDateString()])
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->values()
            ->all();

        $earlyLeaveBlockedToday = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        $checkInStart = Carbon::today()->setTimeFromTimeString($setting->check_in_start_time);
        $checkInEnd = Carbon::today()->setTimeFromTimeString($setting->check_in_end_time);
        $checkOutStart = Carbon::today()->setTimeFromTimeString($setting->check_out_start_time);
        $checkOutEnd = Carbon::today()->setTimeFromTimeString($setting->check_out_end_time);
        $now = now();

        $isHolidayToday = \App\Helpers\HolidayHelper::isHoliday($today);
        $isAttendanceActive = (bool) $setting->is_attendance_active;

        $hasReachedCheckInStart = $now->greaterThanOrEqualTo($checkInStart);
        $isAfterCheckInEnd = $now->greaterThan($checkInEnd);
        $canCheckInNow = $hasReachedCheckInStart && ! $isAfterCheckInEnd && !$isHolidayToday && $isAttendanceActive;

        $hasReachedCheckOutStart = $now->greaterThanOrEqualTo($checkOutStart);
        $isAfterCheckOutEnd = $now->greaterThan($checkOutEnd);
        $canCheckOutNow = $hasReachedCheckOutStart && ! $isAfterCheckOutEnd && !$isHolidayToday && $isAttendanceActive;

        if ($hasApprovedAbsentLeaveToday) {
            $canCheckInNow = false;
            $canCheckOutNow = false;
        }

        $showLeaveForm = $isAttendanceActive;

        return response()->json([
            'data' => [
                'attendance' => $attendance ? (new AttendanceResource($attendance))->toArray($request) : null,
                'recent' => $recent->map(fn ($item) => (new AttendanceResource($item))->toArray($request))->values(),
                'setting' => (new SchoolSettingResource($setting))->toArray($request),
                'can_check_in_now' => $canCheckInNow,
                'has_reached_check_in_start' => $hasReachedCheckInStart,
                'is_after_check_in_end' => $isHolidayToday ? false : $isAfterCheckInEnd,
                'can_check_out_now' => $canCheckOutNow,
                'is_after_check_out_end' => $isHolidayToday ? false : $isAfterCheckOutEnd,
                'today_leave_submission' => $todayLeaveSubmission ? (new LeaveRequestResource($todayLeaveSubmission))->toArray($request) : null,
                'has_approved_absent_leave_today' => $hasApprovedAbsentLeaveToday,
                'show_leave_form' => $showLeaveForm,
                'leave_dates_with_submission' => $absentBlockedDates,
                'absent_blocked_dates' => $absentBlockedDates,
                'early_leave_blocked_today' => $earlyLeaveBlockedToday,
                'is_holiday_today' => $isHolidayToday,
                'is_attendance_active' => $isAttendanceActive,
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
            (float) $request->validated('accuracy'),
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
            (float) $request->validated('accuracy'),
        );

        return response()->json(['message' => $message]);
    }
}

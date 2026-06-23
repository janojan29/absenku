<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attendance\CheckInRequest;
use App\Http\Requests\Attendance\CheckOutRequest;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\SchoolSetting;
use App\Services\Attendance\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
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
            ->map(fn($date) => Carbon::parse($date)->toDateString())
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

        $hasReachedCheckInStart = $now->greaterThanOrEqualTo($checkInStart);
        $isAfterCheckInEnd = $now->greaterThan($checkInEnd);
        $canCheckInNow = $hasReachedCheckInStart && ! $isAfterCheckInEnd && !$isHolidayToday;

        $hasReachedCheckOutStart = $now->greaterThanOrEqualTo($checkOutStart);
        $isAfterCheckOutEnd = $now->greaterThan($checkOutEnd);
        $canCheckOutNow = $hasReachedCheckOutStart && ! $isAfterCheckOutEnd && !$isHolidayToday;

        if ($hasApprovedAbsentLeaveToday) {
            $canCheckInNow = false;
            $canCheckOutNow = false;
        }

        $showLeaveForm = true;

        $recentDates = $recent->pluck('date')->map(fn($d) => $d->toDateString())->all();
        $leaveRequests = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('date', $recentDates)
            ->where('status', 'approved')
            ->get()
            ->groupBy(fn($item) => $item->date->toDateString());

        return view('attendance.index', [
            'attendance' => $attendance,
            'recent' => $recent,
            'setting' => $setting,
            'canCheckInNow' => $canCheckInNow,
            'hasReachedCheckInStart' => $hasReachedCheckInStart,
            'isAfterCheckInEnd' => $isAfterCheckInEnd,
            'canCheckOutNow' => $canCheckOutNow,
            'isAfterCheckOutEnd' => $isAfterCheckOutEnd,
            'todayLeaveSubmission' => $todayLeaveSubmission,
            'hasApprovedAbsentLeaveToday' => $hasApprovedAbsentLeaveToday,
            'showLeaveForm' => $showLeaveForm,
            'absentBlockedDates' => $absentBlockedDates,
            'earlyLeaveBlockedToday' => $earlyLeaveBlockedToday,
            'leaveRequests' => $leaveRequests,
            'isHolidayToday' => $isHolidayToday,
        ]);
    }

    public function checkIn(CheckInRequest $request, AttendanceService $service): RedirectResponse
    {
        $user = $request->user();
        $message = $service->checkIn(
            $user,
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
            (float) $request->validated('accuracy'),
        );

        return back()->with('status', $message);
    }

    public function checkOut(CheckOutRequest $request, AttendanceService $service): RedirectResponse
    {
        $user = $request->user();
        $message = $service->checkOut(
            $user,
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
            (float) $request->validated('accuracy'),
        );

        return back()->with('status', $message);
    }
}

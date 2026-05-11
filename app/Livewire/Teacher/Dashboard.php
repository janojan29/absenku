<?php

namespace App\Livewire\Teacher;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\LeaveRequest;
use App\Models\SchoolSetting;
use App\Models\StudentProfile;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\Attributes\On;

class Dashboard extends Component
{
    public ?int $classRoomId = null;

    #[On('teacher-dashboard.refresh')]
    public function refresh(): void
    {
        // Intentionally empty: Livewire will re-render.
    }

    public function mount(): void
    {
        if ($this->classRoomId === null) {
            $this->classRoomId = ClassRoom::query()->orderBy('name')->value('id');
        }
    }

    public function render()
    {
        $classes = ClassRoom::query()->orderBy('name')->get();
        $setting = SchoolSetting::singleton();

        $today = Carbon::today();

        $studentUserIds = StudentProfile::query()
            ->when($this->classRoomId, fn($q) => $q->where('class_room_id', $this->classRoomId))
            ->pluck('user_id');

        $attendances = Attendance::query()
            ->whereDate('date', $today)
            ->whereIn('user_id', $studentUserIds)
            ->get()
            ->keyBy('user_id');

        $approvedLeaves = LeaveRequest::query()
            ->whereDate('date', $today)
            ->whereIn('user_id', $studentUserIds)
            ->where('status', 'approved')
            ->get()
            ->keyBy('user_id');

        $students = StudentProfile::query()
            ->with(['user', 'classRoom'])
            ->when($this->classRoomId, fn($q) => $q->where('class_room_id', $this->classRoomId))
            ->orderBy('class_room_id')
            ->orderBy('id')
            ->get();

        $lateAt = Carbon::parse($today->toDateString() . ' ' . $setting->check_in_start_time)
            ->addMinutes((int) $setting->late_tolerance_minutes);

        $effectiveStatuses = [];
        foreach ($students as $sp) {
            $attendance = $attendances->get($sp->user_id);
            $leave = $approvedLeaves->get($sp->user_id);

            if ($attendance && $attendance->check_in_at !== null) {
                $checkInAt = Carbon::parse($attendance->check_in_at);
                $effectiveStatuses[$sp->user_id] = $checkInAt->greaterThan($lateAt) ? 'late' : 'present';
            } elseif ($attendance && $attendance->status === 'leave') {
                $effectiveStatuses[$sp->user_id] = 'leave';
            } elseif ($leave) {
                $effectiveStatuses[$sp->user_id] = 'leave';
            } else {
                $effectiveStatuses[$sp->user_id] = 'unknown';
            }
        }

        $counts = [
            'present' => collect($effectiveStatuses)->where(fn($value) => $value === 'present')->count(),
            'late' => collect($effectiveStatuses)->where(fn($value) => $value === 'late')->count(),
            'leave' => collect($effectiveStatuses)->where(fn($value) => $value === 'leave')->count(),
            'unknown' => collect($effectiveStatuses)->where(fn($value) => $value === 'unknown')->count(),
        ];

        return view('livewire.teacher.dashboard', [
            'classes' => $classes,
            'students' => $students,
            'attendances' => $attendances,
            'approvedLeaves' => $approvedLeaves,
            'effectiveStatuses' => $effectiveStatuses,
            'counts' => $counts,
            'today' => $today,
        ]);
    }
}

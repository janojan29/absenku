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
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithPagination;

    public function paginationView()
    {
        return 'vendor.livewire.custom-tailwind';
    }

    public $classRoomId = null;
    public $search = '';

    public $reportStudentId = null;
    public $reportStudentName = '';
    public $reportSubject = '';
    public $reportDescription = '';
    public $showReportModal = false;

    public function openReportModal($studentId, $studentName)
    {
        $this->reportStudentId = $studentId;
        $this->reportStudentName = $studentName;
        $this->reportSubject = '';
        $this->reportDescription = '';
        $this->showReportModal = true;
    }

    public function closeReportModal()
    {
        $this->showReportModal = false;
        $this->reportStudentId = null;
    }

    public function submitReport()
    {
        $this->validate([
            'reportStudentId' => 'required|exists:student_profiles,id',
            'reportSubject' => 'required|string',
            'reportDescription' => 'nullable|string',
        ]);

        $siswa = StudentProfile::with('user')->findOrFail($this->reportStudentId);
        $waktuKejadian = Carbon::now('Asia/Jakarta');

        $absenHariIni = Attendance::where('user_id', $siswa->user_id)
            ->whereDate('date', $waktuKejadian->toDateString())
            ->whereNotNull('check_in_at')
            ->first();

        $waktuHadir = $absenHariIni ? Carbon::parse($absenHariIni->check_in_at)->format('H:i') : 'pagi ini';

        $violation = \App\Models\StudentViolation::create([
            'student_profile_id' => $siswa->id,
            'reported_by' => auth()->id(),
            'subject' => $this->reportSubject,
            'incident_time' => $waktuKejadian->format('H:i:s'),
            'description' => $this->reportDescription,
        ]);

        $pesan = "Yth. Bapak/Ibu Orang Tua/Wali dari *{$siswa->user->name}*.\n\n"
               . "Menginformasikan bahwa ananda tercatat telah *hadir di sekolah* pada pukul *{$waktuHadir} WIB*. "
               . "Namun, pada pukul *{$waktuKejadian->format('H:i')} WIB* (saat Mata Pelajaran {$this->reportSubject}), "
               . "ananda didapati *tidak berada di dalam ruang kelas* tanpa keterangan yang jelas.\n\n"
               . "Mohon bantuan Bapak/Ibu untuk turut mengkonfirmasi keberadaan ananda saat ini demi keamanan, keselamatan, dan kedisiplinan siswa.\n\n"
               . "Terima kasih atas perhatian dan kerjasamanya.";

        $nomorTujuan = $siswa->parent_phone_wa ?? $siswa->parent_whatsapp_number;
        
        if (empty($nomorTujuan)) {
            $this->closeReportModal();
            session()->flash('message', 'Laporan berhasil dicatat, namun WA tidak terkirim karena nomor HP orang tua kosong di data sistem.');
            return;
        }

        \App\Jobs\SendWhatsAppMessage::dispatch(
            $nomorTujuan,
            $pesan,
            \App\Models\StudentViolation::class,
            $violation->id
        );

        $this->closeReportModal();
        session()->flash('message', 'Laporan berhasil dicatat dan dikirim ke WhatsApp Orang Tua!');
    }

    #[On('teacher-dashboard.refresh')]
    public function refresh(): void
    {
        // Intentionally empty: Livewire will re-render.
    }

    public function updatingClassRoomId()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
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
            ->when($this->search, function ($q) {
                $q->whereHas('user', function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('class_room_id')
            ->orderBy('id')
            ->paginate(15);

        $endCheckIn = Carbon::parse($today->toDateString() . ' ' . $setting->check_in_end_time);
        $lateAt = (clone $endCheckIn)->subMinutes((int) $setting->late_tolerance_minutes);

        $isCheckInClosed = Carbon::now('Asia/Jakarta')->greaterThan($endCheckIn);

        $effectiveStatuses = [];
        $statusLabels = [];
        $isHoliday = \App\Helpers\HolidayHelper::isHoliday($today);

        foreach ($studentUserIds as $userId) {
            $attendance = $attendances->get($userId);
            $leave = $approvedLeaves->get($userId);

            if ($attendance && $attendance->check_in_at !== null) {
                // Cek dulu apakah status 'leave' atau 'sick' (izin pulang duluan yang di-ACC)
                if (in_array($attendance->status, ['leave', 'sick'])) {
                    $isSick = $attendance->status === 'sick' || ($leave && $leave->reason === 'sick');
                    $effectiveStatuses[$userId] = $isSick ? 'sick' : 'leave';
                    $statusLabels[$userId] = $isSick ? 'Sakit' : 'Izin';
                } else {
                    $checkInAt = Carbon::parse($attendance->check_in_at);
                    if ($checkInAt->greaterThan($lateAt)) {
                        $effectiveStatuses[$userId] = 'late';
                        $lateMinutes = $attendance->late_minutes;
                        if (empty($lateMinutes)) {
                            $lateMinutes = (int) $checkInAt->diffInMinutes($lateAt);
                        }
                        $statusLabels[$userId] = $lateMinutes > 0 ? "Terlambat ({$lateMinutes} Menit)" : "Terlambat";
                    } else {
                        $effectiveStatuses[$userId] = 'present';
                        $statusLabels[$userId] = 'Hadir';
                    }
                }
            } elseif ($attendance && $attendance->status === 'leave') {
                $isSick = ($leave && $leave->reason === 'sick');
                $effectiveStatuses[$userId] = $isSick ? 'sick' : 'leave';
                $statusLabels[$userId] = $isSick ? 'Sakit' : 'Izin';
            } elseif ($attendance && $attendance->status === 'sick') {
                $effectiveStatuses[$userId] = 'sick';
                $statusLabels[$userId] = 'Sakit';
            } elseif ($leave) {
                $isSick = $leave->reason === 'sick';
                $effectiveStatuses[$userId] = $isSick ? 'sick' : 'leave';
                $statusLabels[$userId] = $isSick ? 'Sakit' : 'Izin';
            } else {
                if ($isHoliday) {
                    $effectiveStatuses[$userId] = 'holiday';
                    $statusLabels[$userId] = 'Libur';
                } elseif ($isCheckInClosed) {
                    $effectiveStatuses[$userId] = 'absent';
                    $statusLabels[$userId] = 'Alfa';
                } else {
                    $effectiveStatuses[$userId] = 'unknown';
                    $statusLabels[$userId] = 'Belum Absen';
                }
            }
        }

        $counts = [
            'present' => collect($effectiveStatuses)->where(fn($value) => $value === 'present')->count(),
            'late' => collect($effectiveStatuses)->where(fn($value) => $value === 'late')->count(),
            'leave' => collect($effectiveStatuses)->where(fn($value) => in_array($value, ['leave', 'sick']))->count(),
            'unknown' => collect($effectiveStatuses)->where(fn($value) => in_array($value, ['unknown', 'absent']))->count(),
        ];

        return view('livewire.teacher.dashboard', [
            'classes' => $classes,
            'students' => $students,
            'attendances' => $attendances,
            'approvedLeaves' => $approvedLeaves,
            'effectiveStatuses' => $effectiveStatuses,
            'statusLabels' => $statusLabels,
            'counts' => $counts,
            'today' => $today,
            'isCheckInClosed' => $isCheckInClosed,
        ]);
    }
}


<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ClassRoom;
use App\Models\StudentProfile;
use App\Models\Attendance;
use App\Models\SchoolSetting;
use App\Models\LeaveRequest;
use App\Services\Attendance\AttendanceService;
use App\Helpers\HolidayHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

class HolidayAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private ClassRoom $classRoom;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'siswa']);
        Role::firstOrCreate(['name' => 'guru_walikelas']);

        $this->classRoom = ClassRoom::create([
            'name' => 'X PPLG 1',
            'jurusan' => 'Pengembangan Perangkat Lunak'
        ]);
    }

    public function test_student_cannot_check_in_on_holiday(): void
    {
        $student = User::factory()->create();
        $student->assignRole('siswa');
        StudentProfile::create([
            'user_id' => $student->id,
            'class_room_id' => $this->classRoom->id,
            'nis' => '1234567890',
        ]);

        $holidayDate = Carbon::parse('2026-01-01'); // New Year's Day (holiday)
        Carbon::setTestNow($holidayDate->copy()->setTime(8, 0, 0));

        $service = app(AttendanceService::class);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Hari ini adalah hari libur. Absen masuk dinonaktifkan.');

        $service->checkIn($student, -6.2, 106.8, 10);
    }

    public function test_student_cannot_check_out_on_holiday(): void
    {
        $student = User::factory()->create();
        $student->assignRole('siswa');
        StudentProfile::create([
            'user_id' => $student->id,
            'class_room_id' => $this->classRoom->id,
            'nis' => '1234567890',
        ]);

        $holidayDate = Carbon::parse('2026-01-01'); // New Year's Day (holiday)
        Carbon::setTestNow($holidayDate->copy()->setTime(16, 0, 0));

        $service = app(AttendanceService::class);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Hari ini adalah hari libur. Absen pulang dinonaktifkan.');

        $service->checkOut($student, -6.2, 106.8, 10);
    }

    public function test_automatic_attendance_commands_skip_holidays(): void
    {
        $student = User::factory()->create();
        $student->assignRole('siswa');
        StudentProfile::create([
            'user_id' => $student->id,
            'class_room_id' => $this->classRoom->id,
            'nis' => '1234567890',
        ]);

        $holidayDate = Carbon::parse('2026-01-01'); // New Year's Day (holiday)

        // Running mark-absent command on a holiday should not create any attendance record
        Artisan::call('attendance:mark-absent', ['--date' => $holidayDate->toDateString()]);

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_students_cannot_submit_leave_requests_on_holidays(): void
    {
        $student = User::factory()->create();
        $student->assignRole('siswa');
        StudentProfile::create([
            'user_id' => $student->id,
            'class_room_id' => $this->classRoom->id,
            'nis' => '1234567890',
        ]);

        $holidayDate = Carbon::parse('2026-01-01'); // New Year's Day (holiday)
        Carbon::setTestNow($holidayDate->copy()->setTime(9, 0, 0));

        $response = $this->actingAs($student)->post(route('leave-requests.store'), [
            'type' => 'absent',
            'reason' => 'sick',
            'keterangan' => 'Sakit demam',
            'leave_date' => $holidayDate->toDateString(),
        ]);

        $response->assertSessionHasErrors('leave');
        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_students_cannot_submit_leave_requests_on_weekends(): void
    {
        $student = User::factory()->create();
        $student->assignRole('siswa');
        StudentProfile::create([
            'user_id' => $student->id,
            'class_room_id' => $this->classRoom->id,
            'nis' => '1234567890',
        ]);

        $saturday = Carbon::parse('2026-06-20'); // A Saturday
        Carbon::setTestNow($saturday->copy()->setTime(9, 0, 0));

        $response = $this->actingAs($student)->post(route('leave-requests.store'), [
            'type' => 'absent',
            'reason' => 'sick',
            'keterangan' => 'Sakit demam',
            'leave_date' => $saturday->toDateString(),
        ]);

        $response->assertSessionHasErrors('leave');
        $this->assertDatabaseCount('leave_requests', 0);
    }
}

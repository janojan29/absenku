<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ClassRoom;
use App\Models\StudentProfile;
use App\Models\Attendance;
use App\Models\SchoolSetting;
use App\Models\LeaveRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Support\Carbon;

class TeacherReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_filter_attendance_by_status(): void
    {
        Role::firstOrCreate(['name' => 'guru_walikelas']);
        Role::firstOrCreate(['name' => 'siswa']);

        $teacher = User::factory()->create();
        $teacher->assignRole('guru_walikelas');

        $classRoom = ClassRoom::create([
            'name' => 'X PPLG 1',
            'jurusan' => 'Pengembangan Perangkat Lunak'
        ]);

        $setting = SchoolSetting::singleton();

        // Create 2 students
        $student1 = User::factory()->create(['name' => 'Student Present']);
        $student1->assignRole('siswa');
        StudentProfile::create([
            'user_id' => $student1->id,
            'class_room_id' => $classRoom->id,
            'nis' => '1234567890',
        ]);

        $student2 = User::factory()->create(['name' => 'Student Absent']);
        $student2->assignRole('siswa');
        StudentProfile::create([
            'user_id' => $student2->id,
            'class_room_id' => $classRoom->id,
            'nis' => '1234567891',
        ]);

        $today = Carbon::parse('2026-10-12'); // A Monday

        // Create present attendance for student 1
        Attendance::create([
            'user_id' => $student1->id,
            'date' => $today->toDateString(),
            'check_in_at' => $today->copy()->setTime(7, 10, 0),
            'check_out_at' => $today->copy()->setTime(15, 30, 0),
            'status' => 'present',
        ]);

        // Student 2 has no attendance (unknown/absent/alfa)

        // Request report index with status=present
        $response = $this->actingAs($teacher)
            ->get(route('teacher.report', [
                'tab' => 'detail',
                'detail_start_date' => $today->toDateString(),
                'detail_end_date' => $today->toDateString(),
                'status' => 'present',
            ]));

        $response->assertStatus(200);

        // Verify that the view has the present student and not the absent/unknown student in the 'rows' collection
        $rows = $response->viewData('rows');
        $this->assertCount(1, $rows);
        $this->assertEquals('Student Present', $rows[0]['Nama']);

        // Request report index with status=unknown (unknown maps to "Belum Absen")
        $responseUnknown = $this->actingAs($teacher)
            ->get(route('teacher.report', [
                'tab' => 'detail',
                'detail_start_date' => $today->toDateString(),
                'detail_end_date' => $today->toDateString(),
                'status' => 'unknown',
            ]));
        $responseUnknown->assertStatus(200);
        $rowsUnknown = $responseUnknown->viewData('rows');
        $this->assertCount(1, $rowsUnknown);
        $this->assertEquals('Student Absent', $rowsUnknown[0]['Nama']);

        // Test that exports also filter correctly
        $responseExcel = $this->actingAs($teacher)
            ->get(route('teacher.report.excel', [
                'detail_start_date' => $today->toDateString(),
                'detail_end_date' => $today->toDateString(),
                'status' => 'present',
            ]));
        $responseExcel->assertStatus(200);

        $responsePdf = $this->actingAs($teacher)
            ->get(route('teacher.report.pdf', [
                'detail_start_date' => $today->toDateString(),
                'detail_end_date' => $today->toDateString(),
                'status' => 'present',
            ]));
        $responsePdf->assertStatus(200);
    }

    public function test_teacher_report_detail_paginates_daily_log_rows(): void
    {
        Role::firstOrCreate(['name' => 'guru_walikelas']);
        Role::firstOrCreate(['name' => 'siswa']);

        $teacher = User::factory()->create();
        $teacher->assignRole('guru_walikelas');

        $classRoom = ClassRoom::create([
            'name' => 'X PPLG 1',
            'jurusan' => 'Pengembangan Perangkat Lunak'
        ]);

        $setting = SchoolSetting::singleton();

        // Create 8 students
        for ($i = 1; $i <= 8; $i++) {
            $student = User::factory()->create(['name' => "Student $i"]);
            $student->assignRole('siswa');
            StudentProfile::create([
                'user_id' => $student->id,
                'class_room_id' => $classRoom->id,
                'nis' => "nis$i",
            ]);
        }

        // Date range of 5 weekdays in October 2026 (no holidays, no weekends)
        $startDate = Carbon::parse('2026-10-12'); // Monday
        $endDate = Carbon::parse('2026-10-16'); // Friday

        // Total expected daily logs = 8 students * 5 days = 40 rows

        // Request page 1
        $responsePage1 = $this->actingAs($teacher)
            ->get(route('teacher.report', [
                'tab' => 'detail',
                'detail_start_date' => $startDate->toDateString(),
                'detail_end_date' => $endDate->toDateString(),
                'page' => 1,
            ]));

        $responsePage1->assertStatus(200);
        $rowsPage1 = $responsePage1->viewData('rows');

        // Page 1 should contain exactly 20 rows (because we set perPage to 20)
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $rowsPage1);
        $this->assertCount(20, $rowsPage1);
        $this->assertEquals(40, $rowsPage1->total());

        // Request page 2
        $responsePage2 = $this->actingAs($teacher)
            ->get(route('teacher.report', [
                'tab' => 'detail',
                'detail_start_date' => $startDate->toDateString(),
                'detail_end_date' => $endDate->toDateString(),
                'page' => 2,
            ]));

        $responsePage2->assertStatus(200);
        $rowsPage2 = $responsePage2->viewData('rows');

        // Page 2 should contain the remaining 20 rows
        $this->assertCount(20, $rowsPage2);
    }

    public function test_teacher_report_shows_present_for_checked_in_student_without_checkout_during_school_hours(): void
    {
        Role::firstOrCreate(['name' => 'guru_walikelas']);
        Role::firstOrCreate(['name' => 'siswa']);

        $teacher = User::factory()->create();
        $teacher->assignRole('guru_walikelas');

        $classRoom = ClassRoom::create([
            'name' => 'X PPLG 1',
            'jurusan' => 'Pengembangan Perangkat Lunak'
        ]);

        $setting = SchoolSetting::singleton();
        $setting->update([
            'check_in_start_time' => '07:00:00',
            'check_out_end_time' => '17:00:00',
        ]);

        $student = User::factory()->create(['name' => 'Test Student Status']);
        $student->assignRole('siswa');
        StudentProfile::create([
            'user_id' => $student->id,
            'class_room_id' => $classRoom->id,
            'nis' => '1111111111',
        ]);

        $today = Carbon::parse('2026-10-12'); // A Monday

        // Checked in today but check_out_at is null
        Attendance::create([
            'user_id' => $student->id,
            'date' => $today->toDateString(),
            'check_in_at' => $today->copy()->setTime(7, 5, 0),
            'check_out_at' => null,
            'status' => 'present',
        ]);

        // Mock time to be 10:00 AM (during school hours, before check-out end)
        Carbon::setTestNow($today->copy()->setTime(10, 0, 0));

        $response = $this->actingAs($teacher)
            ->get(route('teacher.report', [
                'tab' => 'detail',
                'detail_start_date' => $today->toDateString(),
                'detail_end_date' => $today->toDateString(),
            ]));

        $response->assertStatus(200);
        $rows = $response->viewData('rows');

        $this->assertEquals('Hadir', $rows[0]['Status']);

        // Mock time to be past checkout end (e.g. 18:00)
        Carbon::setTestNow($today->copy()->setTime(18, 0, 0));

        $responseEnded = $this->actingAs($teacher)
            ->get(route('teacher.report', [
                'tab' => 'detail',
                'detail_start_date' => $today->toDateString(),
                'detail_end_date' => $today->toDateString(),
            ]));

        $responseEnded->assertStatus(200);
        $rowsEnded = $responseEnded->viewData('rows');

        // Should be Alfa since they missed checkout after check-out period ended
        $this->assertEquals('Alfa', $rowsEnded[0]['Status']);

        Carbon::setTestNow(); // Clean up test time mocking
    }

    public function test_teacher_report_shows_sakit_status_for_approved_sick_leave(): void
    {
        Role::firstOrCreate(['name' => 'guru_walikelas']);
        Role::firstOrCreate(['name' => 'siswa']);

        $teacher = User::factory()->create();
        $teacher->assignRole('guru_walikelas');

        $classRoom = ClassRoom::create([
            'name' => 'X PPLG 1',
            'jurusan' => 'Pengembangan Perangkat Lunak'
        ]);

        $student = User::factory()->create(['name' => 'Sick Student']);
        $student->assignRole('siswa');
        StudentProfile::create([
            'user_id' => $student->id,
            'class_room_id' => $classRoom->id,
            'nis' => '2222222222',
        ]);

        $today = Carbon::parse('2026-10-12'); // A Monday

        // Approved absent leave request with reason sick
        LeaveRequest::create([
            'user_id' => $student->id,
            'type' => 'absent',
            'reason' => 'sick',
            'status' => 'approved',
            'keterangan' => 'Siswa sedang demam',
            'date' => $today->toDateString(),
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('teacher.report', [
                'tab' => 'detail',
                'detail_start_date' => $today->toDateString(),
                'detail_end_date' => $today->toDateString(),
            ]));

        $response->assertStatus(200);
        $rows = $response->viewData('rows');

        $this->assertEquals('Sakit', $rows[0]['Status']);
    }

    public function test_teacher_report_shows_izin_status_for_approved_urgent_leave(): void
    {
        Role::firstOrCreate(['name' => 'guru_walikelas']);
        Role::firstOrCreate(['name' => 'siswa']);

        $teacher = User::factory()->create();
        $teacher->assignRole('guru_walikelas');

        $classRoom = ClassRoom::create([
            'name' => 'X PPLG 1',
            'jurusan' => 'Pengembangan Perangkat Lunak'
        ]);

        $student = User::factory()->create(['name' => 'Urgent Leave Student']);
        $student->assignRole('siswa');
        StudentProfile::create([
            'user_id' => $student->id,
            'class_room_id' => $classRoom->id,
            'nis' => '3333333333',
        ]);

        $today = Carbon::parse('2026-10-12'); // A Monday

        // Approved absent leave request with reason urgent
        LeaveRequest::create([
            'user_id' => $student->id,
            'type' => 'absent',
            'reason' => 'urgent',
            'status' => 'approved',
            'keterangan' => 'Ada urusan keluarga mendadak',
            'date' => $today->toDateString(),
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('teacher.report', [
                'tab' => 'detail',
                'detail_start_date' => $today->toDateString(),
                'detail_end_date' => $today->toDateString(),
            ]));

        $response->assertStatus(200);
        $rows = $response->viewData('rows');

        $this->assertEquals('Izin', $rows[0]['Status']);
    }
}

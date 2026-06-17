<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ClassRoom;
use App\Models\StudentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_view_dashboard_and_pagination(): void
    {
        Role::firstOrCreate(['name' => 'guru']);
        Role::firstOrCreate(['name' => 'siswa']);

        $teacher = User::factory()->create();
        $teacher->assignRole('guru');

        $classRoom = ClassRoom::create([
            'name' => 'X PPLG 1',
            'jurusan' => 'Pengembangan Perangkat Lunak dan Gimmick'
        ]);

        // Create 20 students
        for ($i = 1; $i <= 20; $i++) {
            $student = User::factory()->create([
                'name' => "Student Number $i"
            ]);
            $student->assignRole('siswa');
            StudentProfile::create([
                'user_id' => $student->id,
                'class_room_id' => $classRoom->id,
                'nis' => str_pad((string)$i, 10, '0', STR_PAD_LEFT),
            ]);
        }

        Livewire::actingAs($teacher)
            ->test(\App\Livewire\Teacher\Dashboard::class, ['classRoomId' => $classRoom->id])
            ->assertStatus(200)
            ->assertViewHas('students', function ($students) {
                // Should be paginated to 15 per page
                return $students->count() === 15 && $students->total() === 20;
            });
    }
}

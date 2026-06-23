<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ClassRoom;
use App\Models\StudentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentPromotionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ClassRoom $classA;
    private ClassRoom $classB;
    private ClassRoom $classC;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'siswa']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Class A: Major "PPLG"
        $this->classA = ClassRoom::create([
            'name' => 'X PPLG 1',
            'jurusan' => 'PPLG',
        ]);

        // Class B: Major "PPLG" (Same major)
        $this->classB = ClassRoom::create([
            'name' => 'XI PPLG 1',
            'jurusan' => 'PPLG',
        ]);

        // Class C: Major "AKL" (Different major)
        $this->classC = ClassRoom::create([
            'name' => 'XI AKL 1',
            'jurusan' => 'AKL',
        ]);

        // Create student in Class A
        $student = User::factory()->create();
        $student->assignRole('siswa');
        StudentProfile::create([
            'user_id' => $student->id,
            'class_room_id' => $this->classA->id,
            'nis' => '1111111111',
            'jurusan' => $this->classA->jurusan,
        ]);
    }

    public function test_cannot_promote_to_class_with_different_major(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.students.bulk-class'), [
            'from_class_room_id' => $this->classA->id,
            'to_class_room_id' => $this->classC->id,
        ]);

        $response->assertSessionHasErrors(['to_class_room_id']);
        $this->assertEquals('Jurusan kelas asal dan kelas tujuan harus sama.', session('errors')->first('to_class_room_id'));
    }

    public function test_cannot_promote_to_class_that_has_existing_students(): void
    {
        // Add student to Class B (destination class)
        $studentB = User::factory()->create();
        $studentB->assignRole('siswa');
        StudentProfile::create([
            'user_id' => $studentB->id,
            'class_room_id' => $this->classB->id,
            'nis' => '2222222222',
            'jurusan' => $this->classB->jurusan,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.students.bulk-class'), [
            'from_class_room_id' => $this->classA->id,
            'to_class_room_id' => $this->classB->id,
        ]);

        $response->assertSessionHasErrors(['to_class_room_id']);
        $this->assertEquals('Kelas tujuan masih memiliki siswa. Kosongkan kelas tujuan terlebih dahulu untuk menghindari penumpukan.', session('errors')->first('to_class_room_id'));
    }

    public function test_can_promote_to_empty_class_with_same_major(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.students.bulk-class'), [
            'from_class_room_id' => $this->classA->id,
            'to_class_room_id' => $this->classB->id,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.students.index'));
        
        // Assert student was promoted to Class B
        $this->assertDatabaseHas('student_profiles', [
            'class_room_id' => $this->classB->id,
            'jurusan' => $this->classB->jurusan,
            'nis' => '1111111111',
        ]);
        
        $this->assertDatabaseMissing('student_profiles', [
            'class_room_id' => $this->classA->id,
        ]);
    }

    public function test_can_promote_to_empty_class_with_same_major_case_insensitive_and_padded_spaces(): void
    {
        $classX = ClassRoom::create([
            'name' => 'X PPLG 2',
            'jurusan' => ' pplg ', // padded spaces and lowercase
        ]);

        $classY = ClassRoom::create([
            'name' => 'XI PPLG 2',
            'jurusan' => 'PPLG', // uppercase
        ]);

        $student = User::factory()->create();
        $student->assignRole('siswa');
        StudentProfile::create([
            'user_id' => $student->id,
            'class_room_id' => $classX->id,
            'nis' => '9999999999',
            'jurusan' => $classX->jurusan,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.students.bulk-class'), [
            'from_class_room_id' => $classX->id,
            'to_class_room_id' => $classY->id,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseHas('student_profiles', [
            'class_room_id' => $classY->id,
            'jurusan' => $classY->jurusan,
            'nis' => '9999999999',
        ]);
    }
}

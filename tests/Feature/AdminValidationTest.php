<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ClassRoom;
use App\Models\StudentProfile;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ClassRoom $classRoom;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'siswa']);
        Role::firstOrCreate(['name' => 'guru']);

        $this->admin = User::factory()->createOne();
        $this->admin->assignRole('admin');

        $this->classRoom = ClassRoom::create([
            'name' => 'XII TSM 1',
            'jurusan' => 'Teknik Sepeda Motor'
        ]);
    }

    public function test_student_store_validation_requires_numeric_nis_and_08_phone(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'name' => 'jajang suryaatmaja',
            'password' => 'siswa123',
            'password_confirmation' => 'siswa123',
            'jurusan' => 'Teknik Sepeda Motor',
            'class_room_id' => $this->classRoom->id,
            'nis' => 'abc12345', // invalid: has letters
            'parent_phone_wa' => '081234567890',
            'whatsapp_number' => '6281234567890', // invalid: does not start with 08
        ]);

        $response->assertSessionHasErrors(['nis', 'whatsapp_number']);
    }

    public function test_student_store_validation_passes_with_correct_data(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'name' => 'jajang suryaatmaja',
            'password' => 'siswa123',
            'password_confirmation' => 'siswa123',
            'jurusan' => 'Teknik Sepeda Motor',
            'class_room_id' => $this->classRoom->id,
            'nis' => '0012345678', // valid: numbers only
            'parent_phone_wa' => '081234567890', // valid
            'whatsapp_number' => '089876543210', // valid
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.students.index'));
    }

    public function test_teacher_store_validation_requires_numeric_nip_and_08_phone(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.teachers.store'), [
            'teacher_role' => 'guru',
            'name' => 'dadang supriatna',
            'password' => 'guru1234',
            'password_confirmation' => 'guru1234',
            'nip' => '1990-123-456', // invalid: has hyphens
            'subject' => 'Matematika',
            'whatsapp_number' => '1234567890', // invalid: does not start with 08
        ]);

        $response->assertSessionHasErrors(['nip', 'whatsapp_number']);
    }
}

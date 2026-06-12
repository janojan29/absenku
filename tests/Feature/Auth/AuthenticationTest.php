<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\StudentProfile;
use App\Models\LeaveRequest;
use App\Models\ClassRoom;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'login_identifier' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => 'admin',
        ]);

        $this->post('/login', [
            'login_identifier' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_student_with_pending_leave_request_cannot_login(): void
    {
        $classRoom = ClassRoom::query()->create([
            'name' => 'X RPL',
            'jurusan' => 'RPL',
        ]);

        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => 'siswa',
        ]);

        StudentProfile::query()->create([
            'user_id' => $user->id,
            'class_room_id' => $classRoom->id,
            'nis' => '1234567890',
        ]);

        LeaveRequest::query()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'type' => 'absent',
            'reason' => 'sick',
            'keterangan' => 'Sakit demam',
            'status' => 'pending',
        ]);

        $response = $this->post('/login', [
            'login_identifier' => '1234567890',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login_identifier');
    }
}
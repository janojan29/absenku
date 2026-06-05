<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ForceProfileSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed Spatie roles
        Role::firstOrCreate(['name' => 'siswa']);
        Role::firstOrCreate(['name' => 'guru']);
        Role::firstOrCreate(['name' => 'admin']);
    }

    public function test_siswa_without_phone_or_with_must_change_password_is_redirected_to_profile(): void
    {
        $user = User::factory()->create([
            'whatsapp_number' => null,
            'password' => bcrypt('siswa123'),
        ]);
        $user->assignRole('siswa');

        // Enable middleware enforcement during test session
        $response = $this->actingAs($user)
            ->withSession(['enforce_profile_setup_in_tests' => true])
            ->get(route('dashboard'));

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('warning');
    }

    public function test_siswa_can_access_profile_edit_and_update_routes(): void
    {
        $user = User::factory()->create([
            'whatsapp_number' => null,
            'password' => bcrypt('siswa123'),
        ]);
        $user->assignRole('siswa');

        $this->actingAs($user)
            ->withSession(['enforce_profile_setup_in_tests' => true])
            ->get(route('profile.edit'))
            ->assertOk();
    }

    public function test_siswa_who_completed_setup_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'whatsapp_number' => '081234567890',
            'password' => bcrypt('custom-password'),
        ]);
        $user->assignRole('siswa');

        $this->actingAs($user)
            ->withSession(['enforce_profile_setup_in_tests' => true])
            ->get(route('dashboard'))
            ->assertRedirect(route('attendance.index'));
    }

    public function test_guru_without_phone_or_with_must_change_password_is_redirected_to_profile(): void
    {
        Role::firstOrCreate(['name' => 'guru']);
        $user = User::factory()->create([
            'whatsapp_number' => null,
            'password' => bcrypt('guru1234'),
        ]);
        $user->assignRole('guru');

        $response = $this->actingAs($user)
            ->withSession(['enforce_profile_setup_in_tests' => true])
            ->get(route('dashboard'));

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('warning');
    }

    public function test_guru_walikelas_without_phone_or_with_must_change_password_is_redirected_to_profile(): void
    {
        Role::firstOrCreate(['name' => 'guru_walikelas']);
        $user = User::factory()->create([
            'whatsapp_number' => null,
            'password' => bcrypt('guru1234'),
        ]);
        $user->assignRole('guru_walikelas');

        $response = $this->actingAs($user)
            ->withSession(['enforce_profile_setup_in_tests' => true])
            ->get(route('dashboard'));

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('warning');
    }
}

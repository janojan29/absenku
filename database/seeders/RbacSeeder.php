<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'admin.manage',
            'teacher.view_dashboard',
            'picket.approve_leave',
            'student.attendance',
        ];

        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        $admin = Role::findOrCreate('admin', 'web');
        $guru = Role::findOrCreate('guru', 'web');
        $guruWalikelas = Role::findOrCreate('guru_walikelas', 'web');
        $piket = Role::findOrCreate('petugas_piket', 'web');
        $siswa = Role::findOrCreate('siswa', 'web');

        $admin->givePermissionTo(['admin.manage']);
        $guru->givePermissionTo(['teacher.view_dashboard']);
        $guruWalikelas->givePermissionTo(['teacher.view_dashboard']);
        $piket->givePermissionTo(['teacher.view_dashboard', 'picket.approve_leave']);
        $siswa->givePermissionTo(['student.attendance']);

        $adminUsers = [
            ['email' => 'admin@sekolah.local', 'name' => 'Admin Sekolah'],
        ];

        foreach ($adminUsers as $au) {
            $adminUser = User::query()->firstOrCreate(
                ['email' => $au['email']],
                [
                    'name' => $au['name'],
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                ]
            );

            $adminUser->syncRoles(['admin']);
        }

        // Default 2 user Petugas Piket (maksimal 2)
        $picketUsers = [
            ['email' => 'piket1@sekolah.local', 'name' => 'Petugas Piket 1'],
            ['email' => 'piket2@sekolah.local', 'name' => 'Petugas Piket 2'],
        ];

        foreach ($picketUsers as $pu) {
            $user = User::query()->firstOrCreate(
                ['email' => $pu['email']],
                [
                    'name' => $pu['name'],
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles(['petugas_piket']);
        }

        $this->command?->info('Admin login: admin@sekolah.local / password123');
        $this->command?->info('Petugas Piket login: piket1@sekolah.local / password123');
        $this->command?->info('Petugas Piket login: piket2@sekolah.local / password123');
    }
}

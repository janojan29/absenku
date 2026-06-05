<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateAdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Email tetap dipakai secara internal untuk kebutuhan unik di tabel users,
        // tetapi login di aplikasi menggunakan NIP/NISN.
        $admin = User::updateOrCreate(
            ['email' => 'admin@sekolah.local'],
            [
                'name' => 'Admin Sekolah',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'whatsapp_number' => '+6281234567890',
                'role' => 'admin',
            ]
        );

        $admin->assignRole($adminRole);

        // Pastikan SEMUA user role admin punya NIP (di tabel teachers) supaya bisa login via NIP.
        $adminUsers = User::query()->role('admin')->get();

        foreach ($adminUsers as $adminUser) {
            $teacher = $adminUser->teacher;

            if (! $teacher) {
                $teacher = Teacher::create([
                    'user_id' => $adminUser->id,
                    'nip' => null,
                    'subject' => null,
                ]);
            }

            if (! $teacher->nip) {
                $baseNip = 'ADMIN' . str_pad((string) $adminUser->id, 3, '0', STR_PAD_LEFT);
                $nip = $baseNip;

                // Hindari bentrok unique(nip)
                $suffix = 1;
                while (Teacher::query()->where('nip', $nip)->where('user_id', '!=', $adminUser->id)->exists()) {
                    $nip = $baseNip . '-' . $suffix;
                    $suffix++;
                }

                $teacher->update(['nip' => $nip]);
            }
        }

        $this->command->info('Admin users ensured to have NIP (check teachers table). Default: admin@sekolah.local / password123');
    }
}

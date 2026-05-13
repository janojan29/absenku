<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $old = Role::query()->where('name', 'guru_piket')->where('guard_name', 'web')->first();
        $new = Role::query()->where('name', 'petugas_piket')->where('guard_name', 'web')->first();

        if ($old && !$new) {
            $old->name = 'petugas_piket';
            $old->save();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $old = Role::query()->where('name', 'petugas_piket')->where('guard_name', 'web')->first();
        $new = Role::query()->where('name', 'guru_piket')->where('guard_name', 'web')->first();

        if ($old && !$new) {
            $old->name = 'guru_piket';
            $old->save();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

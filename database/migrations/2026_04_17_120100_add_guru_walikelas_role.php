<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::findOrCreate('teacher.view_dashboard', 'web');
        $role = Role::findOrCreate('guru_walikelas', 'web');
        $role->givePermissionTo($permission);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::query()->where('name', 'guru_walikelas')->where('guard_name', 'web')->first();

        if ($role) {
            $role->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};

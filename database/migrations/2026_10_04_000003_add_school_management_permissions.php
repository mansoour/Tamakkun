<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * v0.2 permissions. Idempotent: only adds what is missing.
 */
return new class extends Migration
{
    /**
     * @var array<string, list<string>>
     */
    private array $grants = [
        'admin' => ['schools.manage', 'users.manage', 'students.view-all', 'students.import'],
        'counselor' => ['students.view-assigned'],
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->grants as $roleName => $permissionNames) {
            $role = Role::findOrCreate($roleName, 'web');

            foreach ($permissionNames as $permissionName) {
                $role->givePermissionTo(Permission::findOrCreate($permissionName, 'web'));
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', array_unique(array_merge(...array_values($this->grants))))
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

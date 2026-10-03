<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the v0.1 roles and permissions.
 *
 * Idempotent: it only adds what is missing and never removes permissions
 * that an administrator granted in production. Names are hard-coded on
 * purpose so this migration keeps working if the enums change later.
 */
return new class extends Migration
{
    /**
     * @var array<string, list<string>>
     */
    private array $grants = [
        'student' => ['student-area.access'],
        'counselor' => ['counselor-area.access'],
        'admin' => ['admin-area.access'],
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
            ->whereIn('name', array_merge(...array_values($this->grants)))
            ->delete();

        Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', array_keys($this->grants))
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

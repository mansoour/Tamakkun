<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * v0.8: reports and log viewers. Idempotent.
 */
return new class extends Migration
{
    /**
     * @var array<string, list<string>>
     */
    private array $grants = [
        'admin' => ['reports.view', 'audit-logs.view', 'email-logs.view'],
        'counselor' => ['reports.view'],
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->grants as $role => $permissions) {
            foreach ($permissions as $permission) {
                Role::findOrCreate($role, 'web')->givePermissionTo(Permission::findOrCreate($permission, 'web'));
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->where('guard_name', 'web')->whereIn('name', ['reports.view', 'audit-logs.view', 'email-logs.view'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

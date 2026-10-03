<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * v0.9: roles/permissions screen and read-only "view as user". Idempotent.
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $permissions = ['roles.manage', 'users.view-as'];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Role::findOrCreate('admin', 'web')->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->where('guard_name', 'web')->whereIn('name', $this->permissions)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

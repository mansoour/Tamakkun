<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Forced password change: a per-user flag set whenever an admin chooses a
 * password for the account, enforced only while the admin-controlled
 * `force_password_change` setting is on. Also adds settings.manage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('status');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('admin', 'web')->givePermissionTo(Permission::findOrCreate('settings.manage', 'web'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });

        Permission::query()->where(['name' => 'settings.manage', 'guard_name' => 'web'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

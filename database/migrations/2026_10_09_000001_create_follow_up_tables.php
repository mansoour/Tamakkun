<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * v0.6 counselor follow-up: manual follow-up status, private notes and
 * automatic alerts, plus the students.follow-up permission.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('follow_up_status', 20)->default('normal')->index()->after('student_code');
            $table->timestamp('follow_up_updated_at')->nullable()->after('follow_up_status');
        });

        Schema::create('counselor_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('counselor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note');
            $table->boolean('is_private')->default(true);
            $table->timestamps();

            $table->index(['student_id', 'created_at']);
        });

        Schema::create('student_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('alert_type', 40);
            $table->string('context_key')->nullable();
            $table->string('severity', 20);
            $table->string('title');
            $table->text('message');
            $table->string('status', 20)->default('open');
            $table->timestamp('generated_at');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['student_id', 'alert_type', 'context_key']);
            $table->index(['status', 'severity']);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('counselor', 'web')->givePermissionTo(Permission::findOrCreate('students.follow-up', 'web'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('student_alerts');
        Schema::dropIfExists('counselor_notes');
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropIndex(['follow_up_status']);
            $table->dropColumn(['follow_up_status', 'follow_up_updated_at']);
        });
        Permission::query()->where(['name' => 'students.follow-up', 'guard_name' => 'web'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

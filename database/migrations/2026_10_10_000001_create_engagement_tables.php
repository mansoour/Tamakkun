<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * v0.7: daily challenge, motivation, announcements and their permissions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_challenges', function (Blueprint $table) {
            $table->id();
            $table->date('challenge_date')->unique();
            $table->string('title')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('challenge_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_challenge_id')->constrained()->cascadeOnDelete();
            $table->string('section', 20);
            $table->string('question_type', 20);
            $table->text('prompt');
            $table->text('explanation')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('challenge_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_question_id')->constrained()->cascadeOnDelete();
            $table->string('label', 500);
            $table->boolean('is_correct')->default(false);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('challenge_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('challenge_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('challenge_option_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_correct');
            $table->timestamp('answered_at');

            $table->unique(['student_id', 'challenge_question_id']);
            $table->index(['student_id', 'answered_at']);
        });

        Schema::create('motivations', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('media_type', 20);
            $table->string('video_url', 2048)->nullable();
            $table->string('image_path')->nullable();
            $table->date('publish_date')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('audience', 20);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['is_published', 'starts_at']);
        });

        Schema::create('announcement_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->string('target_type', 20);
            $table->unsignedBigInteger('target_id');

            $table->index(['target_type', 'target_id']);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $admin = Role::findOrCreate('admin', 'web');
        foreach (['challenges.manage', 'motivations.manage', 'announcements.manage-all'] as $name) {
            $admin->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
        Role::findOrCreate('counselor', 'web')->givePermissionTo(Permission::findOrCreate('announcements.send', 'web'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (['announcement_targets', 'announcements', 'motivations', 'challenge_answers', 'challenge_options', 'challenge_questions', 'daily_challenges'] as $table) {
            Schema::dropIfExists($table);
        }
        Permission::query()->where('guard_name', 'web')
            ->whereIn('name', ['challenges.manage', 'motivations.manage', 'announcements.manage-all', 'announcements.send'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

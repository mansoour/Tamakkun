<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_active_admin_who_can_log_in(): void
    {
        $this->artisan('tamakkun:create-admin')
            ->expectsQuestion('Full name', 'مديرة المنصة')
            ->expectsQuestion('Username (used to log in)', 'principal')
            ->expectsQuestion('Email (optional, for password resets)', '')
            ->expectsQuestion('Password (at least 10 characters)', 'a-long-secret-1')
            ->assertExitCode(0);

        $user = User::where('username', 'principal')->sole();
        $this->assertSame(UserStatus::ACTIVE, $user->status);
        $this->assertTrue($user->can(PermissionName::ACCESS_ADMIN_AREA->value));

        $this->post('/login', ['login' => 'principal', 'password' => 'a-long-secret-1'])->assertRedirect('/dashboard');
    }

    public function test_it_refuses_weak_passwords_and_taken_usernames(): void
    {
        User::factory()->create(['username' => 'taken']);

        $this->artisan('tamakkun:create-admin')
            ->expectsQuestion('Full name', 'مديرة')
            ->expectsQuestion('Username (used to log in)', 'taken')
            ->expectsQuestion('Email (optional, for password resets)', '')
            ->expectsQuestion('Password (at least 10 characters)', 'short')
            ->assertExitCode(1);

        $this->assertSame(1, User::count());
    }
}

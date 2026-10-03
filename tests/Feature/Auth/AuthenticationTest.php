<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('دخول الطالبة')
            ->assertSee('دخول الموجهة الطلابية');
    }

    public function test_student_can_log_in_with_username(): void
    {
        $user = User::factory()->student()->create(['username' => 'st10001']);

        $response = $this->post('/login', ['login' => 'st10001', 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_counselor_can_log_in_with_email(): void
    {
        $user = User::factory()->counselor()->create(['email' => 'counselor@example.com']);

        $this->post('/login', ['login' => 'counselor@example.com', 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
    }

    public function test_successful_login_records_last_login_time(): void
    {
        $user = User::factory()->student()->create(['last_login_at' => null]);

        $this->post('/login', ['login' => $user->username, 'password' => 'password']);

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->student()->create();

        $this->post('/login', ['login' => $user->username, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_unknown_username_is_rejected(): void
    {
        $this->post('/login', ['login' => 'nobody', 'password' => 'password'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    #[DataProvider('inactiveStatuses')]
    public function test_inactive_users_can_not_log_in(UserStatus $status): void
    {
        $user = User::factory()->student()->withStatus($status)->create();

        $this->post('/login', ['login' => $user->username, 'password' => 'password'])
            ->assertSessionHasErrors(['login' => 'هذا الحساب غير مفعّل حاليًا. يرجى التواصل مع إدارة المدرسة.']);

        $this->assertGuest();
    }

    /**
     * @return array<string, array{UserStatus}>
     */
    public static function inactiveStatuses(): array
    {
        return [
            'pending' => [UserStatus::PENDING],
            'suspended' => [UserStatus::SUSPENDED],
            'disabled' => [UserStatus::DISABLED],
        ];
    }

    public function test_login_is_locked_after_too_many_failed_attempts(): void
    {
        $user = User::factory()->student()->create();

        for ($i = 0; $i < LoginRequest::MAX_ATTEMPTS; $i++) {
            $this->post('/login', ['login' => $user->username, 'password' => 'wrong-password']);
        }

        // Even the correct password is refused while the lock is active.
        $this->post('/login', ['login' => $user->username, 'password' => 'password'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
        $this->assertStringContainsString('ثانية', session('errors')->first('login'));
    }

    public function test_login_route_is_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->post('/login', ['login' => "user{$i}", 'password' => 'x']);
        }

        $this->post('/login', ['login' => 'another', 'password' => 'x'])->assertTooManyRequests();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_user_disabled_during_a_session_is_signed_out(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user);
        $user->forceFill(['status' => UserStatus::DISABLED])->save();

        $this->get('/student/dashboard')->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'x', 'email' => 'x@example.com', 'password' => 'password', 'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_status_can_not_be_mass_assigned(): void
    {
        $user = new User(['name' => 'x', 'username' => 'x', 'status' => 'active']);

        $this->assertNull($user->status);
    }
}

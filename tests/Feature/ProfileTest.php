<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_shows_school_managed_account_details(): void
    {
        $user = User::factory()->student()->create(['name' => 'طالبة الاختبار', 'username' => 'st12345']);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('طالبة الاختبار')
            ->assertSee('st12345')
            ->assertSee('تغيير كلمة المرور');
    }

    public function test_users_cannot_change_their_own_profile_details_or_delete_their_account(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)->patch('/profile', ['name' => 'Changed'])->assertMethodNotAllowed();
        $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertMethodNotAllowed();

        $this->assertNotSame('Changed', $user->fresh()->name);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\CounselorProfile;
use App\Models\Grade;
use App\Models\School;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->classroom = Classroom::factory()->create(['name' => '3/1']);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'سارة أحمد علي',
            'username' => 'Sara.Ahmad',
            'email' => '',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
            'school_id' => $this->classroom->schoolId(),
            'grade_id' => $this->classroom->grade_id,
            'classroom_id' => $this->classroom->id,
        ], $overrides);
    }

    public function test_register_page_lists_schools_grades_and_classrooms(): void
    {
        $inactive = Classroom::factory()->for(Grade::factory()->for(AcademicYear::factory()->for(School::factory()->state(['is_active' => false]))))->create();

        $schools = $this->get('/register')->assertOk()->assertSee('إنشاء حساب طالبة')->viewData('schools');

        $this->assertCount(1, $schools);
        $this->assertSame($this->classroom->schoolId(), $schools[0]['id']);
        $this->assertSame([['id' => $this->classroom->id, 'name' => '3/1']], $schools[0]['grades'][0]['classrooms']);
        $this->assertNotContains($inactive->schoolId(), array_column($schools, 'id'));
    }

    public function test_open_registration_creates_an_active_account_and_signs_in(): void
    {
        $this->post('/register', $this->payload())->assertRedirect(route('student.dashboard'));

        $user = User::where('username', 'sara.ahmad')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(UserStatus::ACTIVE, $user->status);
        $this->assertFalse((bool) $user->must_change_password);
        $this->assertTrue($user->hasRole('student'));
        $this->assertSame('sara.ahmad', $user->studentProfile->student_code);
        $this->assertSame($this->classroom->id, $user->studentProfile->classroom_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student.self_registered']);

        $this->get('/student/dashboard')->assertOk();
    }

    public function test_approval_mode_creates_a_pending_account_without_signing_in(): void
    {
        app(SettingsService::class)->set('student_registration', 'approval');

        $this->post('/register', $this->payload())->assertRedirect(route('login'))->assertSessionHas('status');

        $this->assertGuest();
        $this->assertSame(UserStatus::PENDING, User::where('username', 'sara.ahmad')->sole()->status);
    }

    public function test_the_only_counselor_of_the_school_is_assigned(): void
    {
        $counselor = CounselorProfile::factory()->forSchool($this->classroom->grade->academicYear->school)->create();
        $counselor->user->forceFill(['status' => UserStatus::ACTIVE])->save();

        $this->post('/register', $this->payload());

        $this->assertSame($counselor->user_id, User::where('username', 'sara.ahmad')->sole()->studentProfile->counselor_id);
    }

    public function test_classroom_must_match_the_chosen_school_and_grade(): void
    {
        $other = Classroom::factory()->create();

        $this->post('/register', $this->payload(['classroom_id' => $other->id]))->assertSessionHasErrors('classroom_id');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['username' => 'sara.ahmad']);
    }

    public function test_classroom_from_a_past_year_is_refused(): void
    {
        $this->classroom->grade->academicYear->update(['is_current' => false]);

        $this->post('/register', $this->payload())->assertSessionHasErrors('classroom_id');
    }

    public function test_username_must_be_latin_and_unique(): void
    {
        User::factory()->create(['username' => 'taken']);

        $this->post('/register', $this->payload(['username' => 'سارة']))->assertSessionHasErrors('username');
        $this->post('/register', $this->payload(['username' => 'taken']))->assertSessionHasErrors('username');
        $this->post('/register', $this->payload(['password_confirmation' => 'different']))->assertSessionHasErrors('password');
    }

    public function test_login_page_links_to_registration_only_when_open(): void
    {
        $this->get('/login')->assertSee(route('register'), false);

        app(SettingsService::class)->set('student_registration', 'closed');

        $this->get('/login')->assertDontSee(route('register'), false);
        $this->get('/')->assertDontSee(route('register'), false);
    }

    public function test_home_page_shows_the_supervisor(): void
    {
        $this->get('/')->assertOk()->assertSee('أ. فاطمة الشهراني')->assertSee('إنشاء حساب طالبة');
        $this->get('/login')->assertSee('أ. فاطمة الشهراني');
    }
}

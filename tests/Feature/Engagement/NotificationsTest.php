<?php

namespace Tests\Feature\Engagement;

use App\Models\DailyChallenge;
use App\Models\ExamAttempt;
use App\Models\User;
use App\Notifications\DailyChallengeAvailable;
use App\Notifications\ExamReminder;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->student()->create();
    }

    public function test_morning_reminders_send_the_challenge_and_exam_reminders_once(): void
    {
        DailyChallenge::factory()->create();
        ExamAttempt::factory()->booked(7)->create(['student_id' => $this->student->id]);
        ExamAttempt::factory()->booked(1)->create(['student_id' => $this->student->id, 'exam_type' => 'tahsili']);
        ExamAttempt::factory()->booked(3)->create(['student_id' => $this->student->id, 'attempt_number' => 2]);

        $this->artisan('tamakkun:send-reminders')->assertSuccessful();
        $this->artisan('tamakkun:send-reminders')->assertSuccessful();

        $this->assertSame(1, $this->student->notifications()->where('type', DailyChallengeAvailable::class)->count());
        $this->assertSame(2, $this->student->notifications()->where('type', ExamReminder::class)->count());
        $this->assertTrue($this->student->notifications()->get()->contains(fn ($n) => $n->data['title'] === 'اختبار التحصيلي غدًا'));
    }

    public function test_notifications_page_bell_and_mark_as_read(): void
    {
        $this->student->notify(new DailyChallengeAvailable);

        $this->actingAs($this->student)->get('/student/dashboard')->assertSee('1 غير مقروءة');
        $this->actingAs($this->student)->get('/student/notifications')->assertOk()->assertSee('لديك تحدي جديد')->assertSee('جديد');

        $id = $this->student->notifications()->first()->id;
        $this->actingAs($this->student)->get("/student/notifications/{$id}")->assertRedirect(route('student.challenge'));
        $this->assertNotNull($this->student->notifications()->first()->read_at);

        $this->student->notify(new DailyChallengeAvailable);
        $this->actingAs($this->student)->post('/student/notifications/read');
        $this->assertSame(0, $this->student->unreadNotifications()->count());
    }

    public function test_students_cannot_open_someone_elses_notification(): void
    {
        $other = User::factory()->student()->create();
        $other->notify(new DailyChallengeAvailable);

        $this->actingAs($this->student)->get('/student/notifications/'.$other->notifications()->first()->id)->assertNotFound();
    }

    public function test_badges_follow_the_gamification_setting(): void
    {
        ExamAttempt::factory()->withScore(70)->create(['student_id' => $this->student->id]);

        app(SettingsService::class)->set('enable_gamification', false);
        $this->actingAs($this->student)->get('/student/progress')->assertDontSee('أوسمتي');

        app(SettingsService::class)->set('enable_gamification', true);
        $this->actingAs($this->student)->get('/student/progress')
            ->assertSee('أوسمتي')->assertSee('أول اختبار')->assertSee('تم الحصول عليه');
    }
}

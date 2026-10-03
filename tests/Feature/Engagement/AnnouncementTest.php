<?php

namespace Tests\Feature\Engagement;

use App\Models\Announcement;
use App\Models\Classroom;
use App\Models\CounselorProfile;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    private User $counselor;

    private StudentProfile $mine;

    private StudentProfile $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->counselor = CounselorProfile::factory()->create()->user;
        $this->mine = StudentProfile::factory()->assignedTo($this->counselor)->create();
        $this->other = StudentProfile::factory()->create();
    }

    private function notified(StudentProfile $student): int
    {
        return $student->user->notifications()->where('type', AnnouncementPublished::class)->count();
    }

    public function test_admin_announcement_to_all_reaches_every_student(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post('/admin/announcements', [
            'title' => 'إعلان-عام', 'body' => 'نص', 'audience' => 'all',
        ])->assertRedirect('/admin/announcements');

        foreach ([$this->mine, $this->other] as $student) {
            $this->assertSame(1, $this->notified($student));
            $this->actingAs($student->user)->get('/student/dashboard')->assertSee('إعلان-عام');
        }
        $this->assertDatabaseHas('audit_logs', ['action' => 'announcement.created']);
    }

    public function test_counselor_announcement_reaches_only_her_students(): void
    {
        $this->actingAs($this->counselor)->post('/counselor/announcements', [
            'title' => 'لطالباتي', 'body' => 'نص', 'audience' => 'my_students',
        ])->assertRedirect('/counselor/announcements');

        $this->assertSame(1, $this->notified($this->mine));
        $this->assertSame(0, $this->notified($this->other));
        $this->actingAs($this->other->user)->get('/student/dashboard')->assertDontSee('لطالباتي');
    }

    public function test_classroom_and_student_targeting(): void
    {
        $classroom = Classroom::factory()->create();
        $inClass = StudentProfile::factory()->inClassroom($classroom)->assignedTo($this->counselor)->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/announcements', ['title' => 'إعلان-للفصل-٣', 'body' => 'x', 'audience' => 'classroom', 'target_id' => $classroom->id]);
        $this->actingAs($admin)->post('/admin/announcements', ['title' => 'إعلان-فردي-٧', 'body' => 'x', 'audience' => 'student', 'target_id' => $this->other->user_id]);

        $this->actingAs($inClass->user)->get('/student/dashboard')->assertSee('إعلان-للفصل-٣')->assertDontSee('إعلان-فردي-٧');
        $this->actingAs($this->other->user)->get('/student/dashboard')->assertSee('إعلان-فردي-٧')->assertDontSee('إعلان-للفصل-٣');
    }

    public function test_counselor_cannot_target_other_students_or_everyone(): void
    {
        $this->actingAs($this->counselor)->post('/counselor/announcements', ['title' => 'x', 'body' => 'x', 'audience' => 'student', 'target_id' => $this->other->user_id])
            ->assertSessionHasErrors('target_id');
        $this->actingAs($this->counselor)->post('/counselor/announcements', ['title' => 'x', 'body' => 'x', 'audience' => 'all'])
            ->assertSessionHasErrors('audience');
        $this->actingAs($this->mine->user)->post('/counselor/announcements', ['title' => 'x', 'body' => 'x', 'audience' => 'my_students'])
            ->assertForbidden();

        $this->assertDatabaseCount('announcements', 0);
    }

    public function test_scheduled_announcements_wait_until_their_start_time(): void
    {
        $this->actingAs($this->counselor)->post('/counselor/announcements', [
            'title' => 'مجدول', 'body' => 'x', 'audience' => 'my_students', 'starts_at' => now()->addDay()->format('Y-m-d H:i'),
        ]);

        $this->assertSame(0, $this->notified($this->mine));
        $this->actingAs($this->mine->user)->get('/student/dashboard')->assertDontSee('مجدول');

        Carbon::setTestNow(now()->addDays(2));
        $this->artisan('tamakkun:dispatch-announcements')->assertSuccessful();
        $this->artisan('tamakkun:dispatch-announcements')->assertSuccessful();

        $this->assertSame(1, $this->notified($this->mine), 'notified exactly once');
        $this->actingAs($this->mine->user)->get('/student/dashboard')->assertSee('مجدول');
        Carbon::setTestNow();
    }

    public function test_expired_and_withdrawn_announcements_are_hidden(): void
    {
        $this->actingAs($this->counselor)->post('/counselor/announcements', ['title' => 'سيُسحب', 'body' => 'x', 'audience' => 'my_students']);
        $announcement = Announcement::sole();

        $this->actingAs($this->counselor)->post("/counselor/announcements/{$announcement->id}/withdraw")->assertRedirect();
        $this->actingAs($this->mine->user)->get('/student/dashboard')->assertDontSee('سيُسحب');

        $expired = Announcement::create(['title' => 'منتهي', 'body' => 'x', 'audience' => 'all', 'is_published' => true, 'ends_at' => now()->subHour()]);
        $this->actingAs($this->mine->user)->get('/student/dashboard')->assertDontSee($expired->title);
    }
}

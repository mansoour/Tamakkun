<?php

namespace Tests\Feature\Student;

use App\Enums\ActivityEvent;
use App\Enums\ProgressStatus;
use App\Models\ActivityLog;
use App\Models\Content;
use App\Models\Favorite;
use App\Models\StudentContentProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentProgressTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Content $content;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->student()->create();
        $this->content = Content::factory()->create();
    }

    private function progress(?User $student = null): ?StudentContentProgress
    {
        return StudentContentProgress::where('student_id', ($student ?? $this->student)->id)->where('content_id', $this->content->id)->first();
    }

    public function test_opening_content_does_not_start_or_complete_it(): void
    {
        $this->actingAs($this->student)->get("/student/content/{$this->content->slug}")
            ->assertOk()->assertSee('لم يبدأ')->assertSee('ابدأ')->assertSee('أنجزت');

        $this->assertNull($this->progress());
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_student_can_start_content(): void
    {
        $this->actingAs($this->student)->post("/student/content/{$this->content->slug}/start")->assertRedirect();

        $progress = $this->progress();
        $this->assertSame(ProgressStatus::IN_PROGRESS, $progress->status);
        $this->assertNotNull($progress->started_at);
        $this->assertSame(ActivityEvent::CONTENT_STARTED, ActivityLog::sole()->event_type);

        // Starting again is harmless and not logged twice.
        $this->actingAs($this->student)->post("/student/content/{$this->content->slug}/start");
        $this->assertSame(1, ActivityLog::count());
    }

    public function test_student_can_mark_content_complete_once(): void
    {
        $this->actingAs($this->student)->post("/student/content/{$this->content->slug}/complete")->assertRedirect();
        $this->actingAs($this->student)->post("/student/content/{$this->content->slug}/complete");

        $progress = $this->progress();
        $this->assertSame(ProgressStatus::COMPLETED, $progress->status);
        $this->assertSame(100, $progress->progress_percentage);
        $this->assertNotNull($progress->completed_at);
        $this->assertSame(1, ActivityLog::where('event_type', ActivityEvent::CONTENT_COMPLETED)->count());

        $this->actingAs($this->student)->get("/student/content/{$this->content->slug}")
            ->assertSee('مكتمل')->assertSee('التراجع عن الإنجاز');
    }

    public function test_student_can_undo_a_completion(): void
    {
        $this->actingAs($this->student)->post("/student/content/{$this->content->slug}/complete");
        $this->actingAs($this->student)->post("/student/content/{$this->content->slug}/uncomplete");

        $progress = $this->progress();
        $this->assertSame(ProgressStatus::IN_PROGRESS, $progress->status);
        $this->assertNull($progress->completed_at);
    }

    public function test_progress_is_private_to_each_student(): void
    {
        $other = User::factory()->student()->create();

        $this->actingAs($this->student)->post("/student/content/{$this->content->slug}/complete");

        $this->assertNull($this->progress($other));
        $this->actingAs($other)->get("/student/content/{$this->content->slug}")->assertSee('لم يبدأ');
    }

    public function test_hidden_content_cannot_be_started_or_completed(): void
    {
        $draft = Content::factory()->draft()->create();

        foreach (['start', 'complete', 'favorite'] as $action) {
            $this->actingAs($this->student)->post("/student/content/{$draft->slug}/{$action}")->assertNotFound();
        }

        $this->assertDatabaseCount('student_content_progress', 0);
        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_non_students_cannot_record_progress(): void
    {
        $counselor = User::factory()->counselor()->create();

        $this->actingAs($counselor)->post("/student/content/{$this->content->slug}/complete")->assertForbidden();
        $this->assertDatabaseCount('student_content_progress', 0);
    }

    public function test_favorites_can_be_toggled_and_listed(): void
    {
        $this->actingAs($this->student)->post("/student/content/{$this->content->slug}/favorite");
        $this->assertSame(1, Favorite::count());

        $this->actingAs($this->student)->get('/student/favorites')->assertOk()->assertSee($this->content->title);
        $this->actingAs(User::factory()->student()->create())->get('/student/favorites')->assertDontSee($this->content->title);

        $this->actingAs($this->student)->post("/student/content/{$this->content->slug}/favorite");
        $this->assertSame(0, Favorite::count());
    }

    public function test_archived_favorites_are_hidden(): void
    {
        $this->actingAs($this->student)->post("/student/content/{$this->content->slug}/favorite");
        $this->content->update(['archived_at' => now()]);

        $this->actingAs($this->student)->get('/student/favorites')->assertDontSee($this->content->title);
    }

    public function test_list_cards_show_the_students_status(): void
    {
        $this->actingAs($this->student)->post("/student/content/{$this->content->slug}/complete");

        $this->actingAs($this->student)->get('/student/quantitative')->assertSee('مكتمل');
    }

    public function test_login_is_recorded_in_activity_logs(): void
    {
        $this->post('/login', ['login' => $this->student->username, 'password' => 'password']);

        $this->assertSame(ActivityEvent::LOGIN, ActivityLog::sole()->event_type);
    }
}

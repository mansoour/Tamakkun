<?php

namespace Tests\Feature\Student;

use App\Models\Category;
use App\Models\Content;
use App\Models\CounselorProfile;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ContentCompletionService;
use App\Services\SettingsService;
use App\Services\StudentProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProgressSummaryTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->student()->create();
    }

    private function complete(Content $content): void
    {
        app(ContentCompletionService::class)->complete($this->student, $content);
    }

    private function summary(): array
    {
        return app(StudentProgressService::class)->summary($this->student);
    }

    public function test_completion_is_zero_when_there_is_no_content(): void
    {
        $this->assertSame(['completed' => 0, 'total' => 0, 'percentage' => 0], $this->summary()['completion']);
    }

    public function test_completion_counts_only_visible_content_overall_and_per_section(): void
    {
        $quant = Content::factory()->count(4)->create();
        $verbal = Content::factory()->forCategory(Category::factory()->verbal()->create())->create();
        Content::factory()->draft()->create();

        $this->complete($quant[0]);
        $this->complete($verbal);

        $summary = $this->summary();
        $this->assertSame(['completed' => 2, 'total' => 5, 'percentage' => 40], $summary['completion']);
        $this->assertSame(25, $summary['sections']['quantitative']['percentage']);
        $this->assertSame(100, $summary['sections']['verbal']['percentage']);
        $this->assertSame(0, $summary['sections']['tahsili']['total']);

        // Completed content that is later archived no longer counts.
        $verbal->update(['archived_at' => now()]);
        $this->assertSame(['completed' => 1, 'total' => 4, 'percentage' => 25], $this->summary()['completion']);
    }

    public function test_weekly_progress_counts_this_week_against_the_admin_goal(): void
    {
        app(SettingsService::class)->set('weekly_content_goal', 4);
        [$old, $a, $b] = Content::factory()->count(3)->create(['published_at' => now()->subMonth()]);

        Carbon::setTestNow(now()->startOfWeek(Carbon::SUNDAY)->subDays(2));
        $this->complete($old);
        Carbon::setTestNow();

        $this->complete($a);
        $this->complete($b);

        $this->assertSame(['completed' => 2, 'goal' => 4, 'percentage' => 50], $this->summary()['weekly']);
    }

    public function test_streak_counts_consecutive_learning_days(): void
    {
        $contents = Content::factory()->count(4)->create(['published_at' => now()->subMonth()]);
        $completion = app(ContentCompletionService::class);

        foreach ([3, 2, 1] as $i => $daysAgo) {
            Carbon::setTestNow(now()->subDays($daysAgo)->setTime(10, 0));
            $completion->start($this->student, $contents[$i]);
            Carbon::setTestNow();
        }

        // Yesterday was the last active day, so the streak is still alive.
        $this->assertSame(3, $this->summary()['streak']);

        $completion->start($this->student, $contents[3]);
        $this->assertSame(4, $this->summary()['streak']);
    }

    public function test_a_missed_day_breaks_the_streak_and_login_alone_does_not_count(): void
    {
        $content = Content::factory()->create(['published_at' => now()->subMonth()]);

        Carbon::setTestNow(now()->subDays(3));
        app(ContentCompletionService::class)->start($this->student, $content);
        Carbon::setTestNow();

        $this->post('/login', ['login' => $this->student->username, 'password' => 'password']);

        $this->assertSame(0, $this->summary()['streak']);
    }

    public function test_dashboard_and_progress_page_show_real_numbers(): void
    {
        [$a, $b] = Content::factory()->count(2)->create();
        $this->complete($a);
        app(ContentCompletionService::class)->start($this->student, $b);

        $this->actingAs($this->student)->get('/student/dashboard')
            ->assertOk()
            ->assertSee('أنجزتِ 50% من المحتوى المتاح')
            ->assertSee('أكملي من حيث توقفتِ')
            ->assertSee($b->title);

        $this->actingAs($this->student)->get('/student/progress')
            ->assertOk()
            ->assertSee('50%')
            ->assertSee($a->title)
            ->assertSee($b->title);
    }

    public function test_assigned_counselor_sees_the_students_progress(): void
    {
        $counselor = CounselorProfile::factory()->create()->user;
        $profile = StudentProfile::factory()->assignedTo($counselor)->create(['user_id' => $this->student->id]);
        $this->complete(Content::factory()->create());

        $this->actingAs($counselor)->get("/counselor/students/{$profile->id}")
            ->assertOk()
            ->assertSee('نظرة عامة')
            ->assertSee('100%');
    }

    public function test_weekly_goal_setting_is_validated(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put('/admin/settings', ['force_password_change' => '1', 'weekly_content_goal' => 0])
            ->assertSessionHasErrors('weekly_content_goal');

        $this->actingAs($admin)->put('/admin/settings', ['force_password_change' => '1', 'weekly_content_goal' => 8])
            ->assertSessionHasNoErrors();
        $this->assertSame(8, app(SettingsService::class)->get('weekly_content_goal'));
    }
}

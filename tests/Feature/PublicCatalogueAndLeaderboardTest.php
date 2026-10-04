<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\ChallengeAnswer;
use App\Models\Content;
use App\Models\DailyChallenge;
use App\Models\StudentContentProgress;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCatalogueAndLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_browse_titles_but_never_see_urls(): void
    {
        $category = Category::where('section', 'quantitative')->firstOrFail();
        $video = Content::factory()->forCategory($category)->create([
            'title' => 'درس الكسور العامة', 'content_type' => 'video', 'video_url' => 'https://youtu.be/AbCdEfGhIjK',
        ]);
        $link = Content::factory()->forCategory($category)->create([
            'title' => 'لعبة سرية', 'content_type' => 'link', 'external_url' => 'https://wordwall.net/play/secret-123',
        ]);

        $this->get('/content')->assertOk()->assertSee('القدرات الكمي')->assertSee($category->name);

        $this->get('/content/quantitative')->assertOk()
            ->assertSee('درس الكسور العامة')->assertSee('لعبة سرية')
            ->assertSee(route('register'), false)
            ->assertDontSee('AbCdEfGhIjK')->assertDontSee('wordwall.net/play/secret-123');

        $this->get("/student/content/{$video->slug}")->assertRedirect(route('login'));
        $this->get('/content/unknown')->assertNotFound();
    }

    public function test_signed_in_students_open_items_directly(): void
    {
        $content = Content::factory()->forCategory(Category::where('section', 'verbal')->firstOrFail())->create();

        $this->actingAs(User::factory()->student()->create())->get('/content/verbal')
            ->assertSee(route('student.content.show', $content), false);
    }

    public function test_navbar_lists_the_public_pages(): void
    {
        $this->get('/')->assertOk()->assertSeeInOrder(['الرئيسية', 'المحتوى', 'لوحة الشرف', 'عن المنصة', 'مصادر رسمية'])
            ->assertSee('https://mansoour.com', false)->assertSee('Mansoour');
    }

    public function test_leaderboard_ranks_by_points_and_hides_full_names(): void
    {
        $top = User::factory()->student()->create(['name' => 'سارة أحمد العتيبي']);
        $second = User::factory()->student()->create(['name' => 'نورة خالد']);
        $inactive = User::factory()->student()->withStatus(UserStatus::DISABLED)->create(['name' => 'معطلة تماما']);

        $contents = Content::factory()->count(3)->create();
        foreach ([$top, $top, $top, $second, $inactive, $inactive, $inactive] as $i => $student) {
            StudentContentProgress::create(['student_id' => $student->id, 'content_id' => $contents[$i % 3]->id, 'status' => 'completed', 'completed_at' => now()]);
        }
        $question = DailyChallenge::factory()->create()->questions()->first();
        ChallengeAnswer::create(['student_id' => $second->id, 'challenge_question_id' => $question->id, 'challenge_option_id' => $question->options()->first()->id, 'is_correct' => true, 'answered_at' => now()]);

        $this->get('/leaderboard')->assertOk()
            ->assertSeeInOrder(['سارة ع.', '30', 'نورة خ.', '15'])
            ->assertDontSee('سارة أحمد العتيبي')->assertDontSee('معطلة');
    }

    public function test_leaderboard_can_be_hidden(): void
    {
        app(SettingsService::class)->set('show_leaderboard', false);

        $this->get('/leaderboard')->assertNotFound();
        $this->get('/')->assertDontSee(route('leaderboard'), false);
    }
}

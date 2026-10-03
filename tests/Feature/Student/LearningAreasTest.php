<?php

namespace Tests\Feature\Student;

use App\Models\Category;
use App\Models\Chapter;
use App\Models\Content;
use App\Models\ImportantLink;
use App\Models\Source;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningAreasTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->student()->create();
    }

    public function test_quantitative_page_lists_only_visible_content_of_that_section(): void
    {
        $category = Category::where('section', 'quantitative')->where('name', 'النسب')->sole();
        Content::factory()->forCategory($category)->create(['title' => 'منشور-ظاهر']);
        Content::factory()->forCategory($category)->draft()->create(['title' => 'مسودة-مخفية']);
        Content::factory()->forCategory($category)->archived()->create(['title' => 'مؤرشف-مخفي']);
        Content::factory()->forCategory($category)->scheduled()->create(['title' => 'مجدول-مخفي']);
        Content::factory()->forCategory(Category::where('section', 'verbal')->first())->create(['title' => 'لفظي-آخر']);

        $this->actingAs($this->student)->get('/student/quantitative')
            ->assertOk()
            ->assertSee('النسب')
            ->assertSee('منشور-ظاهر')
            ->assertDontSee('مسودة-مخفية')
            ->assertDontSee('مؤرشف-مخفي')
            ->assertDontSee('مجدول-مخفي')
            ->assertDontSee('لفظي-آخر');

        $this->actingAs($this->student)->get('/student/verbal')->assertSee('لفظي-آخر')->assertDontSee('منشور-ظاهر');
    }

    public function test_content_in_a_hidden_category_is_not_listed(): void
    {
        $category = Category::factory()->create(['is_active' => false]);
        Content::factory()->forCategory($category)->create(['title' => 'في-تصنيف-مخفي']);

        $this->actingAs($this->student)->get('/student/quantitative')->assertDontSee('في-تصنيف-مخفي');
    }

    public function test_quantitative_page_filters_by_stage_and_source(): void
    {
        $category = Category::where('section', 'quantitative')->first();
        $source = Source::where('name', 'المعاصر')->sole();
        Content::factory()->forCategory($category)->create(['title' => 'تأسيس-المعاصر', 'stage' => 'foundation', 'source_id' => $source->id]);
        Content::factory()->forCategory($category)->create(['title' => 'مراجعة-بدون-مصدر', 'stage' => 'review']);

        $this->actingAs($this->student)->get('/student/quantitative?stage=review')->assertSee('مراجعة-بدون-مصدر')->assertDontSee('تأسيس-المعاصر');
        $this->actingAs($this->student)->get("/student/quantitative?source_id={$source->id}")->assertSee('تأسيس-المعاصر')->assertDontSee('مراجعة-بدون-مصدر');
    }

    public function test_content_page_embeds_whitelisted_video_and_escapes_body(): void
    {
        $content = Content::factory()->video()->create(['body' => "سطر\n<script>alert(1)</script>"]);

        $this->actingAs($this->student)->get("/student/content/{$content->slug}")
            ->assertOk()
            ->assertSee('https://www.youtube-nocookie.com/embed/AAAAAAAAAAA', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_unpublished_content_pages_are_not_found(): void
    {
        $draft = Content::factory()->draft()->create();
        $archived = Content::factory()->archived()->create();

        $this->actingAs($this->student)->get("/student/content/{$draft->slug}")->assertNotFound();
        $this->actingAs($this->student)->get("/student/content/{$archived->slug}")->assertNotFound();
    }

    public function test_tahsili_subject_page_groups_content_by_chapter(): void
    {
        $subject = Subject::where('name', 'الكيمياء')->sole();
        $chapter = Chapter::factory()->create(['subject_id' => $subject->id, 'name' => 'باب-الأحماض']);
        Content::factory()->tahsili($subject)->create(['title' => 'درس-الأحماض', 'chapter_id' => $chapter->id]);

        $this->actingAs($this->student)->get('/student/achievement')->assertOk()->assertSee('الكيمياء');
        $this->actingAs($this->student)->get("/student/achievement/{$subject->slug}")
            ->assertOk()->assertSee('باب-الأحماض')->assertSee('درس-الأحماض');
    }

    public function test_video_library_lists_only_videos(): void
    {
        Content::factory()->video()->create(['title' => 'مقطع-ظاهر']);
        Content::factory()->create(['title' => 'درس-نصي']);

        $this->actingAs($this->student)->get('/student/videos')->assertOk()->assertSee('مقطع-ظاهر')->assertDontSee('درس-نصي');
    }

    public function test_links_page_shows_active_links_with_official_badge(): void
    {
        ImportantLink::factory()->create(['title' => 'رابط-رسمي', 'is_official' => true]);
        ImportantLink::factory()->create(['title' => 'رابط-مخفي', 'is_active' => false]);

        $this->actingAs($this->student)->get('/student/links')
            ->assertOk()
            ->assertSee('رابط-رسمي')
            ->assertSee('مصدر رسمي')
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertDontSee('رابط-مخفي');
    }

    public function test_empty_areas_show_friendly_empty_states(): void
    {
        $this->actingAs($this->student)->get('/student/quantitative')->assertSee('لا يوجد محتوى منشور بعد');
        $this->actingAs($this->student)->get('/student/links')->assertSee('لم تُضف روابط بعد');
    }

    public function test_only_users_with_student_area_access_can_open_learning_pages(): void
    {
        $counselor = User::factory()->counselor()->create();

        foreach (['/student/quantitative', '/student/verbal', '/student/achievement', '/student/videos', '/student/links'] as $uri) {
            $this->actingAs($counselor)->get($uri)->assertForbidden();
            $this->actingAs($this->student)->get($uri)->assertOk();
        }
    }

    public function test_csp_allows_only_whitelisted_video_frames(): void
    {
        $csp = $this->actingAs($this->student)->get('/student/videos')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('frame-src https://www.youtube-nocookie.com https://player.vimeo.com', $csp);
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Content;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'شرح النسب',
            'section' => 'quantitative',
            'content_type' => 'video',
            'category_id' => Category::factory()->create()->id,
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'stage' => 'foundation',
            'duration_minutes' => 12,
            'is_published' => '1',
        ], $overrides);
    }

    public function test_admin_can_create_video_content(): void
    {
        $this->actingAs($this->admin)->get('/admin/content/create')->assertOk();

        $this->actingAs($this->admin)->post('/admin/content', $this->payload())->assertRedirect('/admin/content');

        $content = Content::sole();
        $this->assertSame('شرح-النسب', $content->slug);
        $this->assertSame(720, $content->duration_seconds);
        $this->assertSame($this->admin->id, $content->created_by);
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $content->embedUrl());
        $this->assertDatabaseHas('audit_logs', ['action' => 'content.created']);
    }

    public function test_duplicate_titles_get_unique_slugs(): void
    {
        $this->actingAs($this->admin)->post('/admin/content', $this->payload());
        $this->actingAs($this->admin)->post('/admin/content', $this->payload());

        $this->assertEqualsCanonicalizing(['شرح-النسب', 'شرح-النسب-2'], Content::pluck('slug')->all());
    }

    public function test_unsupported_or_embedded_video_urls_are_rejected(): void
    {
        foreach (['https://example.com/video/1', 'https://www.youtube.com/watch?v=bad'] as $url) {
            $this->actingAs($this->admin)->post('/admin/content', $this->payload(['video_url' => $url]))
                ->assertSessionHasErrors('video_url');
        }

        $this->actingAs($this->admin)->post('/admin/content', $this->payload(['video_url' => '<iframe src="x"></iframe>']))
            ->assertSessionHasErrors('video_url');

        $this->assertDatabaseCount('contents', 0);
    }

    public function test_video_requires_a_url_and_link_requires_an_external_url(): void
    {
        $this->actingAs($this->admin)->post('/admin/content', $this->payload(['video_url' => '']))->assertSessionHasErrors('video_url');
        $this->actingAs($this->admin)->post('/admin/content', $this->payload(['content_type' => 'link', 'video_url' => '']))->assertSessionHasErrors('external_url');
        $this->actingAs($this->admin)->post('/admin/content', $this->payload(['content_type' => 'link', 'external_url' => 'http://insecure.example']))->assertSessionHasErrors('external_url');
    }

    public function test_category_must_belong_to_the_section(): void
    {
        $verbal = Category::factory()->verbal()->create();

        $this->actingAs($this->admin)->post('/admin/content', $this->payload(['category_id' => $verbal->id]))
            ->assertSessionHasErrors('category_id');
    }

    public function test_tahsili_content_needs_a_consistent_subject_chapter_topic(): void
    {
        $subject = Subject::factory()->create();
        $chapter = Chapter::factory()->create(['subject_id' => $subject->id]);
        $topic = Topic::factory()->create(['chapter_id' => $chapter->id]);
        $otherChapter = Chapter::factory()->create();

        $base = ['section' => 'tahsili', 'content_type' => 'lesson', 'video_url' => '', 'category_id' => ''];

        $this->actingAs($this->admin)->post('/admin/content', $this->payload($base + ['subject_id' => '']))
            ->assertSessionHasErrors('subject_id');
        $this->actingAs($this->admin)->post('/admin/content', $this->payload($base + ['subject_id' => $subject->id, 'chapter_id' => $otherChapter->id]))
            ->assertSessionHasErrors('chapter_id');

        $this->actingAs($this->admin)->post('/admin/content', $this->payload($base + [
            'subject_id' => $subject->id, 'chapter_id' => $chapter->id, 'topic_id' => $topic->id,
        ]))->assertSessionHasNoErrors();

        $content = Content::sole();
        $this->assertNull($content->category_id);
        $this->assertSame($topic->id, $content->topic_id);
    }

    public function test_thumbnails_are_converted_to_webp(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)->post('/admin/content', $this->payload([
            'thumbnail' => UploadedFile::fake()->image('thumb.jpg', 2000, 1000),
        ]))->assertSessionHasNoErrors();

        $path = Content::sole()->thumbnail_path;
        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);

        [$width] = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame(1280, $width);
    }

    public function test_svg_and_disguised_files_are_rejected_as_thumbnails(): void
    {
        Storage::fake('public');

        $svg = UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $fakeJpg = UploadedFile::fake()->createWithContent('x.jpg', '<?php echo 1;');

        foreach ([$svg, $fakeJpg] as $file) {
            $this->actingAs($this->admin)->post('/admin/content', $this->payload(['thumbnail' => $file]))
                ->assertSessionHasErrors('thumbnail');
        }

        $this->assertDatabaseCount('contents', 0);
    }

    public function test_publish_unpublish_archive_and_restore_are_audited(): void
    {
        $content = Content::factory()->draft()->create();

        $this->actingAs($this->admin)->post("/admin/content/{$content->id}/publish");
        $this->assertTrue($content->fresh()->isVisible());

        $this->actingAs($this->admin)->post("/admin/content/{$content->id}/unpublish");
        $this->assertFalse($content->fresh()->is_published);

        $this->actingAs($this->admin)->post("/admin/content/{$content->id}/archive");
        $this->assertNotNull($content->fresh()->archived_at);

        $this->actingAs($this->admin)->post("/admin/content/{$content->id}/restore");
        $this->assertNull($content->fresh()->archived_at);

        $this->assertSame(
            ['content.published', 'content.unpublished', 'content.archived', 'content.restored'],
            AuditLog::orderBy('id')->pluck('action')->all(),
        );
    }

    public function test_admin_can_update_content_and_filter_the_list(): void
    {
        $content = Content::factory()->create(['title' => 'قديم']);

        $this->actingAs($this->admin)->put("/admin/content/{$content->id}", $this->payload([
            'title' => 'عنوان محدّث', 'category_id' => $content->category_id,
        ]))->assertRedirect('/admin/content');

        $this->assertSame('عنوان محدّث', $content->fresh()->title);
        $this->actingAs($this->admin)->get('/admin/content?q=محدّث&status=published')->assertSee('عنوان محدّث');
        $this->actingAs($this->admin)->get('/admin/content?status=draft')->assertDontSee('عنوان محدّث');
    }

    public function test_users_without_content_permission_cannot_manage_content(): void
    {
        $counselor = User::factory()->counselor()->create();

        $this->actingAs($counselor)->get('/admin/content')->assertForbidden();
        $this->actingAs($counselor)->post('/admin/content', $this->payload())->assertForbidden();
    }
}

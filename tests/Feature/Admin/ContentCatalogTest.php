<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Content;
use App\Models\ImportantLink;
use App\Models\Source;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentCatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_migration_seeds_stakeholder_structure_without_inventing_urls(): void
    {
        $this->assertSame(['المعاصر', 'المنصف', 'المفكر', 'هيئة تقويم التعليم والتدريب'], Source::orderBy('id')->pluck('name')->all());
        $this->assertSame(0, Source::whereNotNull('website_url')->count(), 'no provider URL is invented');
        $this->assertSame(17, Category::where('section', 'quantitative')->count());
        $this->assertSame(7, Category::where('section', 'verbal')->count());
        $this->assertSame(['الرياضيات', 'الفيزياء', 'الكيمياء', 'الأحياء'], Subject::orderBy('sort_order')->pluck('name')->all());
    }

    public function test_admin_can_manage_sources(): void
    {
        $this->actingAs($this->admin)->get('/admin/sources')->assertOk()->assertSee('المفكر')->assertSee('لم يُتحقق بعد');

        $this->actingAs($this->admin)->post('/admin/sources', ['name' => 'مصدر جديد', 'is_active' => '1'])
            ->assertRedirect('/admin/sources');

        $source = Source::where('name', 'مصدر جديد')->sole();
        $this->assertSame('مصدر-جديد', $source->slug);

        $this->actingAs($this->admin)->put("/admin/sources/{$source->id}", ['name' => 'مصدر جديد', 'website_url' => 'https://example.com'])
            ->assertRedirect('/admin/sources');
        $this->assertSame('https://example.com', $source->fresh()->website_url);
        $this->assertFalse($source->fresh()->is_active);

        $this->actingAs($this->admin)->post('/admin/sources', ['name' => 'بدون https', 'website_url' => 'http://example.com'])
            ->assertSessionHasErrors('website_url');
    }

    public function test_source_with_content_cannot_be_deleted(): void
    {
        $source = Source::factory()->create();
        Content::factory()->create(['source_id' => $source->id]);

        $this->actingAs($this->admin)->delete("/admin/sources/{$source->id}")->assertSessionHasErrors('delete');
        $this->assertModelExists($source);
    }

    public function test_category_names_are_unique_per_section(): void
    {
        $this->actingAs($this->admin)->post('/admin/categories', ['section' => 'quantitative', 'name' => 'الأعداد'])
            ->assertSessionHasErrors('name');

        $this->actingAs($this->admin)->post('/admin/categories', ['section' => 'verbal', 'name' => 'الأعداد', 'is_active' => '1'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->post('/admin/categories', ['section' => 'tahsili', 'name' => 'x'])
            ->assertSessionHasErrors('section');
    }

    public function test_admin_can_build_the_tahsili_hierarchy(): void
    {
        $subject = Subject::where('name', 'الفيزياء')->sole();

        $this->actingAs($this->admin)->post('/admin/chapters', ['subject_id' => $subject->id, 'name' => 'الحركة'])->assertRedirect('/admin/chapters');
        $chapter = $subject->chapters()->sole();

        $this->actingAs($this->admin)->post('/admin/topics', ['chapter_id' => $chapter->id, 'name' => 'السرعة'])->assertRedirect('/admin/topics');

        $this->actingAs($this->admin)->get("/admin/topics?chapter_id={$chapter->id}")->assertSee('السرعة');
        $this->actingAs($this->admin)->delete("/admin/chapters/{$chapter->id}")->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('audit_logs', ['action' => 'topic.created']);
    }

    public function test_admin_can_manage_important_links(): void
    {
        $this->actingAs($this->admin)->post('/admin/links', [
            'title' => 'رابط موثّق', 'url' => 'https://example.com/register', 'category' => 'qudurat',
            'is_official' => '1', 'is_active' => '1',
        ])->assertRedirect('/admin/links');

        $link = ImportantLink::sole();
        $this->assertTrue($link->is_official);

        $this->actingAs($this->admin)->post('/admin/links', ['title' => 'x', 'url' => 'javascript:alert(1)', 'category' => 'qudurat'])
            ->assertSessionHasErrors('url');

        $this->actingAs($this->admin)->delete("/admin/links/{$link->id}")->assertRedirect('/admin/links');
        $this->assertModelMissing($link);
        $this->assertDatabaseHas('audit_logs', ['action' => 'link.deleted']);
    }

    public function test_catalog_pages_require_permissions(): void
    {
        $counselor = User::factory()->counselor()->create();

        foreach (['/admin/sources', '/admin/categories', '/admin/subjects', '/admin/chapters', '/admin/topics', '/admin/links'] as $uri) {
            $this->actingAs($counselor)->get($uri)->assertForbidden();
            $this->actingAs($this->admin)->get($uri)->assertOk();
        }
    }
}

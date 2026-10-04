<?php

namespace Tests\Feature\Admin;

use App\Enums\ContentType;
use App\Models\Category;
use App\Models\Content;
use App\Models\User;
use App\Support\VideoEmbed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuduratContentImportTest extends TestCase
{
    use RefreshDatabase;

    private function import(): void
    {
        (require database_path('migrations/2026_10_13_000001_import_qudurat_content.php'))->importContent();
    }

    public function test_categories_are_placed_around_the_seeded_ones(): void
    {
        $this->assertSame(
            ['تأسيس أينشتاين', 'استراتيجيات الحل', 'الأعداد'],
            Category::where('section', 'quantitative')->ordered()->limit(3)->pluck('name')->all(),
        );
        $this->assertSame(
            ['أسئلة المقارنة', 'نماذج وتجميعات محلولة', 'اختبارات شاملة ومحاكية', 'مراجع وتجميعات'],
            Category::where('section', 'quantitative')->ordered()->get()->slice(-4)->pluck('name')->values()->all(),
        );
        $this->assertSame(
            ['استيعاب المقروء', 'التناظر اللفظي', 'إكمال الجمل', 'الخطأ السياقي', 'المفردة الشاذة', 'الارتباط والاختلاف'],
            Category::where('section', 'verbal')->ordered()->limit(6)->pluck('name')->all(),
        );
    }

    public function test_every_row_is_imported_once_and_is_valid(): void
    {
        $rows = require database_path('data/qudurat-content.php');

        $this->import();
        $this->import();

        $this->assertSame(count($rows), Content::count(), 're-running the import adds nothing');
        $this->assertSame(count($rows), Content::visible()->count());
        $this->assertSame(0, Content::whereNull('category_id')->count());

        Content::all()->each(function (Content $content) {
            $this->assertSame($content->section, $content->category->section, $content->title);

            if ($content->content_type === ContentType::VIDEO) {
                $this->assertTrue(VideoEmbed::isSupported($content->video_url), $content->title);
            } else {
                $this->assertStringStartsWith('https://', $content->external_url, $content->title);
            }
        });

        $this->assertSame(count($rows), count(array_unique(array_column($rows, 'url'))), 'no link is imported twice');
    }

    public function test_admin_edits_are_never_overwritten(): void
    {
        $this->import();
        $content = Content::where('slug', 'qudurat-q-001')->sole();
        $content->update(['title' => 'عنوان عدّلته الإدارة', 'is_published' => false]);

        $this->import();

        $this->assertSame('عنوان عدّلته الإدارة', $content->fresh()->title);
        $this->assertFalse($content->fresh()->is_published);
    }

    public function test_almunsif_1500_opens_the_quantitative_references_once(): void
    {
        $this->import();
        $migration = require database_path('migrations/2026_10_17_000001_add_almunsif_1500_collection.php');
        $migration->importContent();
        $migration->importContent();

        $content = Content::where('slug', 'almunsif-1500')->sole();
        $this->assertSame('المنصف', $content->source->name);
        $this->assertSame('مراجع وتجميعات', $content->category->name);
        $this->assertSame('quantitative', $content->section->value);

        $this->actingAs(User::factory()->student()->create())->get('/student/quantitative')
            ->assertSeeInOrder(['مراجع وتجميعات', 'تجميعات المنصف 1500 سؤال', 'تجميعات 1447هـ: الخميس – الفترة الأولى']);
    }

    public function test_einstein_course_is_imported_in_lecture_order_at_the_top(): void
    {
        $this->import();
        $migration = require database_path('migrations/2026_10_17_000002_import_einstein_foundation_course.php');
        $migration->importContent();
        $migration->importContent();

        $videos = Content::whereHas('category', fn ($q) => $q->where('name', 'تأسيس أينشتاين'))->ordered()->get();
        $this->assertCount(61, $videos);
        $this->assertTrue($videos->every(fn (Content $c) => VideoEmbed::isSupported($c->video_url) && $c->source->name === 'أينشتاين'));
        $this->assertSame('تأسيس أينشتاين: المحاضرة 1', $videos[1]->title);
        $this->assertSame('تأسيس أينشتاين: المحاضرة 57', $videos->last()->title);

        $this->actingAs(User::factory()->student()->create())->get('/student/quantitative')
            ->assertSeeInOrder(['تأسيس أينشتاين', 'المحاضرة 1', 'تكملة المحاضرة 24', 'المحاضرة 25', 'استراتيجيات الحل']);
    }

    public function test_student_sees_imported_content_in_its_category(): void
    {
        $this->import();
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get('/student/quantitative')->assertOk()
            ->assertSeeInOrder(['استراتيجيات الحل', 'دورة القدرات الكمي: استراتيجية التجريب', 'الهندسة', 'لعبة هندسة 1']);

        $this->actingAs($student)->get('/student/verbal')->assertOk()
            ->assertSeeInOrder(['المفردة الشاذة', 'لعبة المفردة الشاذة 1']);
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Enums\ContentType;
use App\Models\Chapter;
use App\Models\Content;
use App\Models\Subject;
use App\Models\User;
use App\Support\VideoEmbed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TahsiliMathContentImportTest extends TestCase
{
    use RefreshDatabase;

    private function import(): void
    {
        (require database_path('migrations/2026_10_14_000001_import_tahsili_math_content.php'))->importContent();
    }

    private function data(): array
    {
        return require database_path('data/tahsili-math-content.php');
    }

    public function test_chapters_and_topics_are_created_in_order(): void
    {
        $math = Subject::where('slug', 'الرياضيات')->sole();

        $this->assertSame(array_keys($this->data()['chapters']), $math->chapters()->pluck('name')->all());
        $this->assertSame(
            $this->data()['chapters']['أول ثانوي – الفصل الأول: التبرير والبرهان'],
            Chapter::where('name', 'أول ثانوي – الفصل الأول: التبرير والبرهان')->sole()->topics()->pluck('name')->all(),
        );
    }

    public function test_every_row_is_imported_once_and_is_valid(): void
    {
        $rows = $this->data()['contents'];

        $this->import();
        $this->import();

        $this->assertSame(count($rows), Content::count(), 're-running the import adds nothing');
        $this->assertSame(count($rows), Content::visible()->where('section', 'tahsili')->count());
        $this->assertSame(count($rows), count(array_unique(array_column($rows, 'url'))), 'no link is imported twice');

        Content::with(['subject', 'chapter', 'topic'])->get()->each(function (Content $content) {
            $this->assertSame('الرياضيات', $content->subject->name, $content->title);
            $this->assertSame($content->subject_id, $content->chapter->subject_id, $content->title);
            $this->assertTrue($content->topic === null || $content->topic->chapter_id === $content->chapter_id, $content->title);

            if ($content->content_type === ContentType::VIDEO) {
                $this->assertTrue(VideoEmbed::isSupported($content->video_url), $content->title);
            } else {
                $this->assertStringStartsWith('https://', $content->external_url, $content->title);
            }
        });
    }

    public function test_admin_edits_are_never_overwritten(): void
    {
        $this->import();
        $content = Content::where('slug', 'tahsili-math-001')->sole();
        $content->update(['title' => 'عنوان عدّلته الإدارة', 'is_published' => false]);

        $this->import();

        $this->assertSame('عنوان عدّلته الإدارة', $content->fresh()->title);
        $this->assertFalse($content->fresh()->is_published);
    }

    public function test_student_sees_chapters_topics_and_items_in_order(): void
    {
        $this->import();
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get('/student/achievement/الرياضيات')->assertOk()
            ->assertSeeInOrder([
                'أول ثانوي – الفصل الأول: التبرير والبرهان', 'دورة فن التحصيلي', 'دورة فن التحصيلي: التبرير والبرهان 1',
                'التبرير الاستقرائي والتخمين', 'لعبة: التبرير الاستقرائي والتخمين (أول ثانوي)', 'حل لعبة: التبرير الاستقرائي والتخمين (أول ثانوي)',
                'ثالث ثانوي – الفصل الثامن: النهايات والاشتقاق', 'اختبار إلكتروني: ثالث ثانوي النهايات والاشتقاق',
                'تجميعات تحصيلي 1447هـ', 'مراجع وتجميعات',
            ]);
    }
}

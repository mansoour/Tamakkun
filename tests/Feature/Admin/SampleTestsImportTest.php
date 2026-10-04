<?php

namespace Tests\Feature\Admin;

use App\Models\Content;
use App\Models\ImportantLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SampleTestsImportTest extends TestCase
{
    use RefreshDatabase;

    private function import(): void
    {
        (require database_path('migrations/2026_10_13_000001_import_qudurat_content.php'))->importContent();
        (require database_path('migrations/2026_10_15_000001_import_sample_tests.php'))->importContent();
    }

    public function test_sample_tests_are_imported_once_into_their_categories_without_a_source(): void
    {
        $this->import();
        $this->import();

        $rows = require database_path('data/sample-tests-content.php');
        $imported = Content::whereIn('slug', array_column($rows, 'slug'))->with('category')->get()->keyBy('slug');

        $this->assertCount(count($rows), $imported);
        foreach ($rows as $row) {
            $content = $imported[$row['slug']];
            $this->assertSame($row['category'], $content->category->name, $row['title']);
            $this->assertSame($row['url'], $content->external_url);
            $this->assertNull($content->source_id, 'the store is not recorded as a source');
        }
    }

    public function test_placement_test_opens_its_category_and_others_follow_the_last_e_test(): void
    {
        $this->import();
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get('/student/quantitative')->assertOk()
            ->assertSeeInOrder(['اختبارات شاملة ومحاكية', 'اختبار تحديد المستوى – القسم الكمي', 'اختبار إلكتروني: قدرات كمي متنوع 1'])
            ->assertSeeInOrder(['اختبار هندسة 6 (كمي)', 'اختبار تجريبي: هندسة 1', 'اختبار تجريبي: هندسة 4']);

        $this->actingAs($student)->get('/student/verbal')->assertOk()
            ->assertSeeInOrder(['اختبار إلكتروني: المفردة الشاذة 2', 'اختبار تجريبي: المفردة الشاذة', 'ملف المفردة الشاذة']);
    }

    public function test_every_external_link_shows_the_privacy_warning(): void
    {
        $this->import();
        $student = User::factory()->student()->create();
        ImportantLink::factory()->create();

        $this->actingAs($student)->get('/student/content/sample-test-q-2')->assertOk()
            ->assertSee('تنبيه قبل فتح الرابط')->assertSee('0500000000');

        $this->actingAs($student)->get('/student/links')->assertOk()->assertSee('تنبيه قبل فتح الرابط')->assertSee('مصدر رسمي');

        $video = Content::where('content_type', 'video')->firstOrFail();
        $this->actingAs($student)->get("/student/content/{$video->slug}")->assertOk()->assertDontSee('تنبيه قبل فتح الرابط');
    }
}

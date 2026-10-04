<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Real القدرات content (الكمي واللفظي) from the team's own Google Site
 * "العب وتدرب قدرات وتحصيلي": course videos, games, e-tests and reference
 * files, placed in the category each item is about. Rows live in
 * database/data/qudurat-content.php (see docs/content-sources.md).
 *
 * Idempotent like the other seeding migrations: categories, the source and
 * content rows are inserted only when missing, and a category is moved only
 * while it still has its originally seeded position, so admin edits in
 * production are never overwritten.
 */
return new class extends Migration
{
    private const SOURCE_SLUG = 'play-training-qudrat-tahsyle';

    /**
     * New categories and their position. Seeded categories at or after that
     * position move down by one to make room.
     *
     * @var array<string, array<string, int>>
     */
    private const NEW_CATEGORIES = [
        'quantitative' => ['استراتيجيات الحل' => 0, 'أسئلة المقارنة' => 18, 'نماذج وتجميعات محلولة' => 19, 'اختبارات شاملة ومحاكية' => 20, 'مراجع وتجميعات' => 21],
        'verbal' => ['المفردة الشاذة' => 4, 'اختبارات شاملة ومحاكية' => 8, 'مراجع وتجميعات' => 9],
    ];

    /**
     * Categories seeded by 2026_10_06_000002, in their original order.
     *
     * @var array<string, list<string>>
     */
    private const SEEDED = [
        'quantitative' => ['الأعداد', 'الكسور', 'النسب', 'النسبة المئوية', 'النسب والتناسب', 'المتوسط', 'الأسس والجذور', 'المعادلات',
            'السرعة والزمن والمسافة', 'العمل والإنجاز', 'الاحتمالات', 'الإحصاء', 'الهندسة', 'المساحات', 'المحيط', 'الزوايا', 'المسائل اللفظية الكمية'],
        'verbal' => ['استيعاب المقروء', 'التناظر اللفظي', 'إكمال الجمل', 'الخطأ السياقي', 'الارتباط والاختلاف', 'المفردات', 'العلاقات بين الكلمات'],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::SEEDED as $section => $names) {
            $firstNew = min(self::NEW_CATEGORIES[$section]);
            foreach ($names as $i => $name) {
                if ($i >= $firstNew) {
                    DB::table('categories')->where(['section' => $section, 'slug' => $this->slug($name), 'sort_order' => $i])
                        ->update(['sort_order' => $i + 1, 'updated_at' => $now]);
                }
            }

            foreach (self::NEW_CATEGORIES[$section] as $name => $position) {
                DB::table('categories')->insertOrIgnore([
                    'section' => $section, 'name' => $name, 'slug' => $this->slug($name),
                    'sort_order' => $position, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        DB::table('sources')->insertOrIgnore([
            'name' => 'منصة العب وتدرب قدرات وتحصيلي',
            'slug' => self::SOURCE_SLUG,
            'website_url' => 'https://sites.google.com/view/play-training-qudrat-tahsyle',
            'description' => 'موقع الفريق على Google Sites: ألعاب تدريبية واختبارات إلكترونية ودورات ومراجع للقدرات والتحصيلي.',
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        // Feature tests build their own content; QuduratContentImportTest calls importContent() directly.
        if (! app()->runningUnitTests()) {
            $this->importContent();
        }
    }

    public function importContent(): void
    {
        $now = now();
        $sourceId = DB::table('sources')->where('slug', self::SOURCE_SLUG)->value('id');

        $categoryIds = DB::table('categories')->get(['id', 'section', 'slug'])
            ->mapWithKeys(fn ($c) => ["{$c->section}|{$c->slug}" => $c->id]);

        foreach (array_chunk($this->rows(), 100) as $chunk) {
            DB::table('contents')->insertOrIgnore(array_map(fn (array $row) => [
                'title' => $row['title'],
                'slug' => $row['slug'],
                'description' => $row['description'],
                'content_type' => $row['content_type'],
                'section' => $row['section'],
                'category_id' => $categoryIds[$row['section'].'|'.$this->slug($row['category'])],
                'source_id' => $sourceId,
                'stage' => $row['stage'],
                'video_url' => $row['content_type'] === 'video' ? $row['url'] : null,
                'external_url' => $row['content_type'] === 'video' ? null : $row['url'],
                'sort_order' => $row['sort_order'],
                'is_published' => true,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk));
        }
    }

    public function down(): void
    {
        DB::table('contents')->whereIn('slug', array_column($this->rows(), 'slug'))->delete();

        foreach (self::NEW_CATEGORIES as $section => $names) {
            foreach (array_keys($names) as $name) {
                $category = DB::table('categories')->where(['section' => $section, 'slug' => $this->slug($name)]);
                if (! DB::table('contents')->where('category_id', (clone $category)->value('id'))->exists()) {
                    $category->delete();
                }
            }
        }

        if (! DB::table('contents')->whereIn('source_id', DB::table('sources')->where('slug', self::SOURCE_SLUG)->select('id'))->exists()) {
            DB::table('sources')->where('slug', self::SOURCE_SLUG)->delete();
        }
    }

    /**
     * @return list<array{slug: string, section: string, category: string, title: string, description: string, content_type: string, stage: string, url: string, sort_order: int}>
     */
    private function rows(): array
    {
        return require database_path('data/qudurat-content.php');
    }

    private function slug(string $name): string
    {
        return trim(preg_replace('/[^\p{L}\p{N}]+/u', '-', $name), '-');
    }
};

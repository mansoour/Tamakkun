<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * «تأسيس أينشتاين»: a 61-video YouTube foundation course for القدرات الكمي
 * (database/data/einstein-foundation.php), in its own category at the top of
 * the quantitative section. Added at the stakeholder's request; see
 * docs/content-sources.md for the source caveat.
 *
 * Idempotent: the category is created (and the others moved down) only once,
 * and each video only when its slug is missing.
 */
return new class extends Migration
{
    private const CATEGORY = 'تأسيس أينشتاين';

    public function up(): void
    {
        $now = now();

        if (! DB::table('categories')->where(['section' => 'quantitative', 'slug' => $this->slug(self::CATEGORY)])->exists()) {
            DB::table('categories')->where('section', 'quantitative')->increment('sort_order');
            DB::table('categories')->insert([
                'section' => 'quantitative', 'name' => self::CATEGORY, 'slug' => $this->slug(self::CATEGORY),
                'description' => 'كنز مجاني حقيقي! يقدّم «أينشتاين» شرح قدرات استثنائيًا يفكّك أعقد مفاهيم الكمي ويجعلها سهلة الفهم للجميع. ابدئي من المحاضرة الأولى وتابعي بالترتيب.',
                'sort_order' => 0, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        DB::table('sources')->insertOrIgnore([
            'name' => 'أينشتاين', 'slug' => 'einstein', 'website_url' => null,
            'description' => 'سلسلة «تأسيس أينشتاين» لشرح القسم الكمي في القدرات من الصفر.',
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        if (! app()->runningUnitTests()) {
            $this->importContent();
        }
    }

    public function importContent(): void
    {
        $now = now();
        $categoryId = DB::table('categories')->where(['section' => 'quantitative', 'slug' => $this->slug(self::CATEGORY)])->value('id');
        $sourceId = DB::table('sources')->where('slug', 'einstein')->value('id');
        $existing = DB::table('contents')->where('slug', 'like', 'einstein-%')->pluck('slug')->all();

        $rows = [];
        foreach (require database_path('data/einstein-foundation.php') as $i => $video) {
            if (in_array($video['slug'], $existing, true)) {
                continue;
            }
            $rows[] = [
                'title' => $video['title'], 'slug' => $video['slug'],
                'description' => $i === 0
                    ? 'ابدئي بهذا المقطع: طريقة مذاكرة القدرات وترتيب المصادر قبل محاضرات التأسيس.'
                    : 'محاضرة من سلسلة «تأسيس أينشتاين» لشرح القسم الكمي من الصفر. تابعي المحاضرات بالترتيب.',
                'content_type' => 'video', 'section' => 'quantitative', 'category_id' => $categoryId, 'source_id' => $sourceId,
                'stage' => 'foundation', 'video_url' => $video['url'], 'duration_seconds' => $video['duration_seconds'],
                'sort_order' => $i, 'is_published' => true, 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('contents')->insert($rows);
        }
    }

    public function down(): void
    {
        DB::table('contents')->whereIn('slug', array_column(require database_path('data/einstein-foundation.php'), 'slug'))->delete();

        $category = DB::table('categories')->where(['section' => 'quantitative', 'slug' => $this->slug(self::CATEGORY)]);
        if (! DB::table('contents')->where('category_id', (clone $category)->value('id'))->exists() && $category->delete()) {
            DB::table('categories')->where('section', 'quantitative')->where('sort_order', '>', 0)->decrement('sort_order');
        }

        if (! DB::table('contents')->whereIn('source_id', DB::table('sources')->where('slug', 'einstein')->select('id'))->exists()) {
            DB::table('sources')->where('slug', 'einstein')->delete();
        }
    }

    private function slug(string $name): string
    {
        return trim(preg_replace('/[^\p{L}\p{N}]+/u', '-', $name), '-');
    }
};

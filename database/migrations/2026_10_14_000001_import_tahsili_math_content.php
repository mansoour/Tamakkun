<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Real التحصيلي – الرياضيات content from the team's own Google Site
 * "العب وتدرب قدرات وتحصيلي": the chapters of the three secondary grades
 * with their games and solution videos, دورة فن التحصيلي, e-tests,
 * تجميعات 1446 و1447, مبادرة جسم and reference files. Rows live in
 * database/data/tahsili-math-content.php (see docs/content-sources.md).
 *
 * Idempotent like the other seeding migrations: chapters, topics and
 * content are inserted only when missing, so admin edits are never
 * overwritten.
 */
return new class extends Migration
{
    private const SOURCE_SLUG = 'play-training-qudrat-tahsyle';

    public function up(): void
    {
        $now = now();
        $data = $this->data();

        DB::table('subjects')->insertOrIgnore([
            'name' => $data['subject'], 'slug' => $this->slug($data['subject']), 'sort_order' => 0,
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $subjectId = DB::table('subjects')->where('slug', $this->slug($data['subject']))->value('id');

        $position = 0;
        foreach ($data['chapters'] as $chapter => $topics) {
            DB::table('chapters')->insertOrIgnore([
                'subject_id' => $subjectId, 'name' => $chapter, 'sort_order' => $position++, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $chapterId = DB::table('chapters')->where(['subject_id' => $subjectId, 'name' => $chapter])->value('id');

            foreach ($topics as $i => $topic) {
                DB::table('topics')->insertOrIgnore([
                    'chapter_id' => $chapterId, 'name' => $topic, 'sort_order' => $i, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        // Feature tests build their own content; TahsiliMathContentImportTest calls importContent() directly.
        if (! app()->runningUnitTests()) {
            $this->importContent();
        }
    }

    public function importContent(): void
    {
        $now = now();
        $data = $this->data();

        DB::table('sources')->insertOrIgnore([
            'name' => 'منصة العب وتدرب قدرات وتحصيلي',
            'slug' => self::SOURCE_SLUG,
            'website_url' => 'https://sites.google.com/view/play-training-qudrat-tahsyle',
            'description' => 'موقع الفريق على Google Sites: ألعاب تدريبية واختبارات إلكترونية ودورات ومراجع للقدرات والتحصيلي.',
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $sourceId = DB::table('sources')->where('slug', self::SOURCE_SLUG)->value('id');
        $subjectId = DB::table('subjects')->where('slug', $this->slug($data['subject']))->value('id');

        $chapterIds = DB::table('chapters')->where('subject_id', $subjectId)->pluck('id', 'name');
        $topicIds = DB::table('topics')->whereIn('chapter_id', $chapterIds)->get(['id', 'chapter_id', 'name'])
            ->mapWithKeys(fn ($t) => ["{$t->chapter_id}|{$t->name}" => $t->id]);

        foreach (array_chunk($data['contents'], 100) as $chunk) {
            DB::table('contents')->insertOrIgnore(array_map(function (array $row) use ($chapterIds, $topicIds, $subjectId, $sourceId, $now) {
                $chapterId = $chapterIds[$row['chapter']];

                return [
                    'title' => $row['title'],
                    'slug' => $row['slug'],
                    'description' => $row['description'],
                    'content_type' => $row['content_type'],
                    'section' => 'tahsili',
                    'subject_id' => $subjectId,
                    'chapter_id' => $chapterId,
                    'topic_id' => $row['topic'] === null ? null : $topicIds["{$chapterId}|{$row['topic']}"],
                    'source_id' => $sourceId,
                    'stage' => $row['stage'],
                    'video_url' => $row['content_type'] === 'video' ? $row['url'] : null,
                    'external_url' => $row['content_type'] === 'video' ? null : $row['url'],
                    'sort_order' => $row['sort_order'],
                    'is_published' => true,
                    'published_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $chunk));
        }
    }

    public function down(): void
    {
        $data = $this->data();
        DB::table('contents')->whereIn('slug', array_column($data['contents'], 'slug'))->delete();

        $subjectId = DB::table('subjects')->where('slug', $this->slug($data['subject']))->value('id');
        foreach (array_keys($data['chapters']) as $chapter) {
            $chapterId = DB::table('chapters')->where(['subject_id' => $subjectId, 'name' => $chapter])->value('id');
            if ($chapterId && ! DB::table('contents')->where('chapter_id', $chapterId)->exists()) {
                DB::table('topics')->where('chapter_id', $chapterId)->delete();
                DB::table('chapters')->where('id', $chapterId)->delete();
            }
        }
    }

    /**
     * @return array{subject: string, chapters: array<string, list<string>>, contents: list<array{slug: string, chapter: string, topic: ?string, title: string, description: string, content_type: string, stage: string, url: string, sort_order: int}>}
     */
    private function data(): array
    {
        return require database_path('data/tahsili-math-content.php');
    }

    private function slug(string $name): string
    {
        return trim(preg_replace('/[^\p{L}\p{N}]+/u', '-', $name), '-');
    }
};

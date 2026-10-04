<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Twelve free sample tests (Google Forms) for القدرات, each placed in the
 * category it tests. No source is recorded: the page that lists them belongs
 * to a paid store we are not affiliated with. Rows live in
 * database/data/sample-tests-content.php.
 *
 * Idempotent: rows are inserted only when their slug is missing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! app()->runningUnitTests()) {
            $this->importContent();
        }
    }

    public function importContent(): void
    {
        $now = now();
        $categoryIds = DB::table('categories')->get(['id', 'section', 'slug'])
            ->mapWithKeys(fn ($c) => ["{$c->section}|{$c->slug}" => $c->id]);

        foreach ($this->rows() as $row) {
            $categoryId = $categoryIds[$row['section'].'|'.$this->slug($row['category'])] ?? null;

            if ($categoryId === null || DB::table('contents')->where('slug', $row['slug'])->exists()) {
                continue;
            }

            // Placement tests open the category; the others follow its last e-test
            // (equal sort_order sorts by id, so they land just after it).
            $sortOrder = $row['first'] ? 0 : (int) (DB::table('contents')->where('category_id', $categoryId)
                ->where('stage', '!=', 'review')->max('sort_order') ?? 0);

            DB::table('contents')->insert([
                'title' => $row['title'],
                'slug' => $row['slug'],
                'description' => $row['description'],
                'content_type' => 'practice',
                'section' => $row['section'],
                'category_id' => $categoryId,
                'source_id' => null,
                'stage' => $row['stage'],
                'external_url' => $row['url'],
                'sort_order' => $sortOrder,
                'is_published' => true,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('contents')->whereIn('slug', array_column($this->rows(), 'slug'))->delete();
    }

    /**
     * @return list<array{slug: string, section: string, category: string, title: string, description: string, stage: string, url: string, first: bool}>
     */
    private function rows(): array
    {
        return require database_path('data/sample-tests-content.php');
    }

    private function slug(string $name): string
    {
        return trim(preg_replace('/[^\p{L}\p{N}]+/u', '-', $name), '-');
    }
};

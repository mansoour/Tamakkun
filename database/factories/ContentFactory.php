<?php

namespace Database\Factories;

use App\Enums\ContentSection;
use App\Enums\ContentStage;
use App\Enums\ContentType;
use App\Models\Category;
use App\Models\Content;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Fake content only — no real provider URLs.
 *
 * @extends Factory<Content>
 */
class ContentFactory extends Factory
{
    public function definition(): array
    {
        $n = fake()->unique()->numberBetween(1, 999999);

        return [
            'title' => "درس تجريبي {$n}",
            'slug' => "test-content-{$n}",
            'description' => 'وصف تجريبي لأغراض التطوير.',
            'content_type' => ContentType::LESSON,
            'section' => ContentSection::QUANTITATIVE,
            'category_id' => Category::factory(),
            'stage' => ContentStage::FOUNDATION,
            'sort_order' => 0,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['archived_at' => now()]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => ['published_at' => now()->addWeek()]);
    }

    public function forCategory(Category $category): static
    {
        return $this->state(fn () => ['section' => $category->section, 'category_id' => $category->id]);
    }

    public function tahsili(?Subject $subject = null): static
    {
        return $this->state(fn () => [
            'section' => ContentSection::TAHSILI,
            'category_id' => null,
            'subject_id' => $subject?->id ?? Subject::factory(),
        ]);
    }

    /**
     * A video with a syntactically valid but fake YouTube id.
     */
    public function video(): static
    {
        return $this->state(fn () => [
            'content_type' => ContentType::VIDEO,
            'video_url' => 'https://www.youtube.com/watch?v=AAAAAAAAAAA',
        ]);
    }
}

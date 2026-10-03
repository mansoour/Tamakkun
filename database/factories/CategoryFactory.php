<?php

namespace Database\Factories;

use App\Enums\ContentSection;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $n = fake()->unique()->numberBetween(1, 99999);

        return [
            'section' => ContentSection::QUANTITATIVE,
            'name' => "تصنيف تجريبي {$n}",
            'slug' => "test-category-{$n}",
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function verbal(): static
    {
        return $this->state(fn () => ['section' => ContentSection::VERBAL]);
    }
}

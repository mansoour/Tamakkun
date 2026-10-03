<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chapter>
 */
class ChapterFactory extends Factory
{
    public function definition(): array
    {
        return ['subject_id' => Subject::factory(), 'name' => 'باب تجريبي '.fake()->unique()->numberBetween(1, 99999), 'sort_order' => 0];
    }
}

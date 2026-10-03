<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    public function definition(): array
    {
        $n = fake()->unique()->numberBetween(1, 99999);

        return ['name' => "مادة تجريبية {$n}", 'slug' => "test-subject-{$n}", 'sort_order' => 0, 'is_active' => true];
    }
}

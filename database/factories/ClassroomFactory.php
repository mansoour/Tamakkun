<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Grade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'grade_id' => Grade::factory(),
            'name' => '3/'.fake()->unique()->numberBetween(1, 999),
            'sort_order' => 0,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'مدرسة '.fake()->unique()->numberBetween(1, 99999).' الثانوية (تجريبية)',
            'city' => fake()->city(),
            'is_active' => true,
        ];
    }
}

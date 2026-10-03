<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(1440, 1499);

        return [
            'school_id' => School::factory(),
            'name' => "{$year}–".($year + 1),
            'is_current' => true,
        ];
    }
}

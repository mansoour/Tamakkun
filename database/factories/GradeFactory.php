<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Grade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grade>
 */
class GradeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => 'الثالث الثانوي',
            'level' => 12,
            'sort_order' => 0,
        ];
    }
}

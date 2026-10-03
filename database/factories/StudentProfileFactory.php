<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'school_id' => School::factory(),
            'classroom_id' => null,
            'counselor_id' => null,
            'student_code' => fn (array $attributes) => User::find($attributes['user_id'])->username,
        ];
    }

    /**
     * Place the student in a classroom (and that classroom's school).
     */
    public function inClassroom(Classroom $classroom): static
    {
        return $this->state(fn () => [
            'classroom_id' => $classroom->id,
            'school_id' => $classroom->schoolId(),
        ]);
    }

    public function forSchool(School $school): static
    {
        return $this->state(fn () => ['school_id' => $school->id]);
    }

    public function assignedTo(User $counselor): static
    {
        return $this->state(fn () => ['counselor_id' => $counselor->id]);
    }
}

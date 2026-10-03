<?php

namespace Database\Factories;

use App\Models\CounselorProfile;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CounselorProfile>
 */
class CounselorProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->counselor(),
            'school_id' => School::factory(),
            'job_title' => 'موجهة طلابية',
        ];
    }

    public function forSchool(School $school): static
    {
        return $this->state(fn () => ['school_id' => $school->id]);
    }
}

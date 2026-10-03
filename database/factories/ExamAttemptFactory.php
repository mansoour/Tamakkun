<?php

namespace Database\Factories;

use App\Enums\ExamBookingStatus;
use App\Enums\ExamType;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAttempt>
 */
class ExamAttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => User::factory()->student(),
            'exam_type' => ExamType::QUDURAT,
            'attempt_number' => 1,
            'booking_status' => ExamBookingStatus::NOT_BOOKED,
        ];
    }

    public function booked(int $daysFromNow = 14): static
    {
        return $this->state(fn () => ['booking_status' => ExamBookingStatus::BOOKED, 'exam_date' => now()->addDays($daysFromNow)->toDateString()]);
    }

    public function withScore(int $score, int $daysAgo = 30): static
    {
        return $this->state(fn () => [
            'booking_status' => ExamBookingStatus::RESULT_RECEIVED,
            'exam_date' => now()->subDays($daysAgo)->toDateString(),
            'score' => $score,
        ]);
    }
}

<?php

namespace App\Models;

use App\Enums\ExamBookingStatus;
use App\Enums\ExamType;
use Database\Factories\ExamAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One sitting (or planned sitting) of Qudurat or Tahsili. Write through
 * App\Services\ExamAttemptService so changes are numbered and audited.
 */
#[Fillable(['student_id', 'exam_type', 'attempt_number', 'booking_status', 'exam_date', 'score', 'target_score', 'notes'])]
class ExamAttempt extends Model
{
    /** @use HasFactory<ExamAttemptFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'exam_type' => ExamType::class,
            'booking_status' => ExamBookingStatus::class,
            'exam_date' => 'date',
            'score' => 'integer',
            'target_score' => 'integer',
            'attempt_number' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}

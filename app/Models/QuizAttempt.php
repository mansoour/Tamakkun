<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One submission of a quiz. The score columns are kept even if the quiz's
 * questions are later edited (which removes the per-question answers).
 */
#[Fillable(['quiz_id', 'student_id', 'correct_count', 'question_count', 'percentage', 'passed', 'submitted_at'])]
class QuizAttempt extends Model
{
    protected function casts(): array
    {
        return [
            'correct_count' => 'integer',
            'question_count' => 'integer',
            'percentage' => 'integer',
            'passed' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * @return HasMany<QuizAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'challenge_question_id', 'challenge_option_id', 'is_correct', 'answered_at'])]
class ChallengeAnswer extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['is_correct' => 'boolean', 'answered_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<ChallengeQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(ChallengeQuestion::class, 'challenge_question_id');
    }

    /**
     * @return BelongsTo<ChallengeOption, $this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(ChallengeOption::class, 'challenge_option_id');
    }
}

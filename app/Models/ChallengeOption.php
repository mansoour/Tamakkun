<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

/**
 * `is_correct` is hidden from serialisation so the answer never leaks into
 * JSON sent to the browser before the student answers.
 */
#[Fillable(['challenge_question_id', 'label', 'is_correct', 'sort_order'])]
#[Hidden(['is_correct'])]
class ChallengeOption extends Model
{
    protected function casts(): array
    {
        return ['is_correct' => 'boolean', 'sort_order' => 'integer'];
    }
}

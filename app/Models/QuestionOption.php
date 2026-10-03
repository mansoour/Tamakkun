<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

/**
 * `is_correct` is hidden from serialisation so answers never leak to the
 * browser before the student submits.
 */
#[Fillable(['quiz_question_id', 'label', 'is_correct', 'sort_order'])]
#[Hidden(['is_correct'])]
class QuestionOption extends Model
{
    protected function casts(): array
    {
        return ['is_correct' => 'boolean', 'sort_order' => 'integer'];
    }
}

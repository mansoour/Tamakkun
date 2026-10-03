<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['quiz_attempt_id', 'quiz_question_id', 'question_option_id', 'is_correct'])]
class QuizAnswer extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }
}

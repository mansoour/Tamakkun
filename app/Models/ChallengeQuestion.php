<?php

namespace App\Models;

use App\Enums\ContentSection;
use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['daily_challenge_id', 'section', 'question_type', 'prompt', 'explanation', 'sort_order'])]
class ChallengeQuestion extends Model
{
    protected function casts(): array
    {
        return [
            'section' => ContentSection::class,
            'question_type' => QuestionType::class,
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<DailyChallenge, $this>
     */
    public function challenge(): BelongsTo
    {
        return $this->belongsTo(DailyChallenge::class, 'daily_challenge_id');
    }

    /**
     * @return HasMany<ChallengeOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(ChallengeOption::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<ChallengeAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(ChallengeAnswer::class);
    }
}

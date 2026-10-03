<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * تحدي اليوم: one challenge per date (Asia/Riyadh), usually one
 * quantitative and one verbal question.
 */
#[Fillable(['challenge_date', 'title', 'is_published', 'notified_at', 'created_by'])]
class DailyChallenge extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'challenge_date' => 'date',
            'is_published' => 'boolean',
            'notified_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<ChallengeQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(ChallengeQuestion::class)->orderBy('sort_order');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForToday(Builder $query): void
    {
        $query->where('is_published', true)->whereDate('challenge_date', today());
    }
}

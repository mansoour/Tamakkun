<?php

namespace App\Models;

use App\Enums\AnnouncementAudience;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Announcement to all students, a counselor's students, a classroom or a
 * student. Recipients are resolved by App\Services\AnnouncementService.
 */
#[Fillable(['title', 'body', 'author_id', 'audience', 'starts_at', 'ends_at', 'is_published', 'notified_at'])]
class Announcement extends Model
{
    protected function casts(): array
    {
        return [
            'audience' => AnnouncementAudience::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_published' => 'boolean',
            'notified_at' => 'datetime',
        ];
    }

    /**
     * Published and inside its display window.
     *
     * @param  Builder<self>  $query
     */
    public function scopeCurrent(Builder $query): void
    {
        $query->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return HasMany<AnnouncementTarget, $this>
     */
    public function targets(): HasMany
    {
        return $this->hasMany(AnnouncementTarget::class);
    }
}

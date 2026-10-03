<?php

namespace App\Models;

use App\Enums\ContentDifficulty;
use App\Enums\ContentSection;
use App\Enums\ContentStage;
use App\Enums\ContentType;
use App\Support\VideoEmbed;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * One learning item (video, lesson, link, article, practice or quiz).
 * Create and change content through App\Services\ContentService.
 */
#[Fillable([
    'title', 'slug', 'description', 'body', 'content_type', 'section', 'category_id', 'source_id',
    'subject_id', 'chapter_id', 'topic_id', 'stage', 'difficulty', 'video_url', 'external_url',
    'thumbnail_path', 'duration_seconds', 'sort_order', 'is_published', 'published_at', 'archived_at', 'created_by',
])]
class Content extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'content_type' => ContentType::class,
            'section' => ContentSection::class,
            'stage' => ContentStage::class,
            'difficulty' => ContentDifficulty::class,
            'duration_seconds' => 'integer',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * Content a student may see: published, publish date reached, not archived.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_published', true)
            ->whereNull('archived_at')
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    public function isVisible(): bool
    {
        return $this->is_published && $this->archived_at === null
            && ($this->published_at === null || $this->published_at->isPast());
    }

    public function embedUrl(): ?string
    {
        return VideoEmbed::embedUrl($this->video_url);
    }

    public function thumbnailUrl(): ?string
    {
        return $this->thumbnail_path ? Storage::disk('public')->url($this->thumbnail_path) : null;
    }

    public function durationMinutes(): ?int
    {
        return $this->duration_seconds ? (int) ceil($this->duration_seconds / 60) : null;
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Source, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * @return BelongsTo<Chapter, $this>
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * @return BelongsTo<Topic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

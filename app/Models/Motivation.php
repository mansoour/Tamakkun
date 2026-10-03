<?php

namespace App\Models;

use App\Enums\MotivationType;
use App\Support\VideoEmbed;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * دفعة اليوم item: practical encouragement (tip, 15-minute task, habit…),
 * optionally with a whitelisted video or an optimised image.
 */
#[Fillable(['title', 'content', 'media_type', 'video_url', 'image_path', 'publish_date', 'is_active', 'created_by'])]
class Motivation extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'media_type' => MotivationType::class,
            'publish_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Active and not scheduled for a future date.
     *
     * @param  Builder<self>  $query
     */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('publish_date')->orWhereDate('publish_date', '<=', today()));
    }

    public function embedUrl(): ?string
    {
        return VideoEmbed::embedUrl($this->video_url);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}

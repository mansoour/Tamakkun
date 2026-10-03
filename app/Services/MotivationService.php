<?php

namespace App\Services;

use App\Models\Motivation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * دفعة اليوم: picks today's item and manages the library.
 */
class MotivationService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ImageOptimizer $images,
    ) {}

    /**
     * An item dated today wins; otherwise a stable daily rotation over the
     * undated active items, so every student sees the same item all day.
     */
    public function today(): ?Motivation
    {
        $dated = Motivation::where('is_active', true)->whereDate('publish_date', today())->latest('id')->first();

        if ($dated) {
            return $dated;
        }

        $pool = Motivation::where('is_active', true)->whereNull('publish_date')->orderBy('id')->get();

        return $pool->isEmpty() ? null : $pool[now()->dayOfYear % $pool->count()];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(?Motivation $motivation, array $data, ?UploadedFile $image, bool $removeImage, ?User $author): Motivation
    {
        return DB::transaction(function () use ($motivation, $data, $image, $removeImage, $author) {
            $isNew = $motivation === null;
            $motivation ??= new Motivation(['created_by' => $author?->id]);
            $motivation->fill(Arr::only($data, ['title', 'content', 'media_type', 'video_url', 'publish_date']) + ['is_active' => (bool) ($data['is_active'] ?? false)]);

            if ($image || $removeImage) {
                $this->images->delete($motivation->image_path);
                $motivation->image_path = $image ? $this->images->storeAsWebp($image, 'motivations') : null;
            }

            $motivation->save();
            $this->audit->record($isNew ? 'motivation.created' : 'motivation.updated', $motivation, null, ['title' => $motivation->title]);

            return $motivation;
        });
    }

    public function delete(Motivation $motivation): void
    {
        $this->images->delete($motivation->image_path);
        $motivation->delete();
        $this->audit->record('motivation.deleted', $motivation, ['title' => $motivation->title], null);
    }
}

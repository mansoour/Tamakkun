<?php

namespace App\Services;

use App\Enums\ContentSection;
use App\Models\Content;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Create, update, publish, unpublish, archive and restore learning content.
 * Every change is audited.
 */
class ContentService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ImageOptimizer $images,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated StoreContentRequest data
     */
    public function create(array $data, ?UploadedFile $thumbnail, ?User $author): Content
    {
        return DB::transaction(function () use ($data, $thumbnail, $author) {
            $attributes = $this->normalise($data);
            $attributes['slug'] = $this->uniqueSlug($attributes['title']);
            $attributes['created_by'] = $author?->id;

            if ($thumbnail) {
                $attributes['thumbnail_path'] = $this->images->storeAsWebp($thumbnail, 'content-thumbnails');
            }

            $content = Content::create($attributes);
            $this->audit->record('content.created', $content, null, Arr::except($content->attributesToArray(), ['body']));

            return $content;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Content $content, array $data, ?UploadedFile $thumbnail, bool $removeThumbnail = false): Content
    {
        return DB::transaction(function () use ($content, $data, $thumbnail, $removeThumbnail) {
            $content->fill($this->normalise($data));

            if ($thumbnail || $removeThumbnail) {
                $this->images->delete($content->thumbnail_path);
                $content->thumbnail_path = $thumbnail ? $this->images->storeAsWebp($thumbnail, 'content-thumbnails') : null;
            }

            $this->saveAudited($content, 'content.updated');

            return $content;
        });
    }

    public function publish(Content $content): void
    {
        $content->fill(['is_published' => true, 'archived_at' => null]);
        $content->published_at ??= now();
        $this->saveAudited($content, 'content.published');
    }

    public function unpublish(Content $content): void
    {
        $content->is_published = false;
        $this->saveAudited($content, 'content.unpublished');
    }

    public function archive(Content $content): void
    {
        $content->fill(['archived_at' => now(), 'is_published' => false]);
        $this->saveAudited($content, 'content.archived');
    }

    public function restore(Content $content): void
    {
        $content->archived_at = null;
        $this->saveAudited($content, 'content.restored');
    }

    private function saveAudited(Content $content, string $action): void
    {
        $changes = Arr::except($content->getDirty(), ['body']);
        $original = array_intersect_key($content->getRawOriginal(), $changes);
        $content->save();

        if ($changes !== []) {
            $this->audit->record($action, $content, $original, $changes);
        }
    }

    /**
     * Clears fields that do not apply to the chosen section and converts
     * minutes to seconds.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalise(array $data): array
    {
        $section = ContentSection::from($data['section']);

        if ($section->usesCategories()) {
            $data['subject_id'] = $data['chapter_id'] = $data['topic_id'] = null;
        } else {
            $data['category_id'] = null;
        }

        if (array_key_exists('duration_minutes', $data)) {
            $data['duration_seconds'] = $data['duration_minutes'] ? (int) $data['duration_minutes'] * 60 : null;
        }

        $data['is_published'] = (bool) ($data['is_published'] ?? false);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return Arr::except($data, ['duration_minutes', 'thumbnail', 'remove_thumbnail']);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::limit(trim(preg_replace('/[^\p{L}\p{N}]+/u', '-', $title), '-'), 80, '') ?: 'content';
        $slug = $base;
        $i = 2;

        while (Content::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}

<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Chapter;
use App\Models\ImportantLink;
use App\Models\Source;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Create/update/delete for sources, categories, the Tahsili hierarchy and
 * important links. Audited, with deletion blocked while records depend on
 * the item (deactivate it instead).
 */
class ContentStructureService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $class
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function create(string $class, array $data): Model
    {
        return DB::transaction(function () use ($class, $data) {
            if (in_array($class, [Source::class, Category::class, Subject::class], true) && blank($data['slug'] ?? null)) {
                $data['slug'] = $this->slug($class, $data['name'], $data['section'] ?? null);
            }

            $model = $class::create($data);
            $this->audit->record($this->action($model, 'created'), $model, null, $model->only(array_keys($data)));

            return $model;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        $model->fill($data);
        $changes = $model->getDirty();
        $original = array_intersect_key($model->getRawOriginal(), $changes);
        $model->save();

        if ($changes !== []) {
            $this->audit->record($this->action($model, 'updated'), $model, $original, $changes);
        }

        return $model;
    }

    public function delete(Model $model): void
    {
        if ($reason = $this->blockingReason($model)) {
            throw ValidationException::withMessages(['delete' => $reason]);
        }

        $snapshot = $model->attributesToArray();
        $model->delete();
        $this->audit->record($this->action($model, 'deleted'), $model, $snapshot, null);
    }

    public function blockingReason(Model $model): ?string
    {
        return match (true) {
            $model instanceof Source && $model->contents()->exists() => 'لا يمكن حذف المصدر لوجود محتوى مرتبط به. يمكنك إيقافه بدلًا من الحذف.',
            $model instanceof Category && $model->contents()->exists() => 'لا يمكن حذف التصنيف لوجود محتوى مرتبط به. يمكنك إيقافه بدلًا من الحذف.',
            $model instanceof Subject && ($model->chapters()->exists() || $model->contents()->exists()) => 'لا يمكن حذف المادة لوجود فصول أو محتوى مرتبط بها.',
            $model instanceof Chapter && ($model->topics()->exists() || $model->contents()->exists()) => 'لا يمكن حذف الباب لوجود موضوعات أو محتوى مرتبط به.',
            $model instanceof Topic && $model->contents()->exists() => 'لا يمكن حذف الموضوع لوجود محتوى مرتبط به.',
            default => null,
        };
    }

    /**
     * @param  class-string<Model>  $class
     */
    private function slug(string $class, string $name, ?string $section): string
    {
        $base = trim(preg_replace('/[^\p{L}\p{N}]+/u', '-', $name), '-') ?: Str::lower(Str::random(6));
        $slug = $base;
        $i = 2;

        while ($class::query()->where('slug', $slug)->when($section, fn ($q) => $q->where('section', $section))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    private function action(Model $model, string $verb): string
    {
        $name = match (true) {
            $model instanceof Source => 'source',
            $model instanceof Category => 'category',
            $model instanceof Subject => 'subject',
            $model instanceof Chapter => 'chapter',
            $model instanceof Topic => 'topic',
            $model instanceof ImportantLink => 'link',
            default => 'record',
        };

        return "{$name}.{$verb}";
    }
}

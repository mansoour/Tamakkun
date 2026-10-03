<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ChapterRequest;
use App\Models\Chapter;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChapterController extends CatalogController
{
    protected function modelClass(): string
    {
        return Chapter::class;
    }

    protected function routeBase(): string
    {
        return 'admin.chapters';
    }

    protected function pluralTitle(): string
    {
        return 'أبواب التحصيلي';
    }

    protected function singularTitle(): string
    {
        return 'باب';
    }

    /**
     * @return array<int, string>
     */
    private function subjects(): array
    {
        return Subject::orderBy('sort_order')->pluck('name', 'id')->all();
    }

    protected function query(Request $request): Builder
    {
        return Chapter::query()->with('subject')->withCount(['topics', 'contents'])
            ->when($request->integer('subject_id'), fn ($q, $id) => $q->where('subject_id', $id))
            ->orderBy('subject_id')->orderBy('sort_order')->orderBy('name');
    }

    protected function filters(): array
    {
        return [['name' => 'subject_id', 'label' => 'المادة', 'options' => $this->subjects()]];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'subject_id', 'label' => 'المادة', 'type' => 'select', 'options' => $this->subjects(), 'required' => true],
            ['name' => 'name', 'label' => 'اسم الباب', 'type' => 'text', 'required' => true],
            ['name' => 'sort_order', 'label' => 'الترتيب', 'type' => 'number'],
        ];
    }

    protected function columns(): array
    {
        return [
            'الباب' => fn (Chapter $c) => $c->name,
            'المادة' => fn (Chapter $c) => $c->subject->name,
            'الموضوعات' => fn (Chapter $c) => (string) $c->topics_count,
            'المحتوى' => fn (Chapter $c) => (string) $c->contents_count,
        ];
    }

    protected function defaults(Request $request): array
    {
        return ['subject_id' => $request->integer('subject_id') ?: null, 'sort_order' => 0];
    }

    public function store(ChapterRequest $request): RedirectResponse
    {
        return $this->storeRecord($request->validated());
    }

    public function update(ChapterRequest $request, int $chapter): RedirectResponse
    {
        return $this->updateRecord($chapter, $request->validated());
    }
}

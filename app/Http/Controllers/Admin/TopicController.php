<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\TopicRequest;
use App\Models\Chapter;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TopicController extends CatalogController
{
    protected function modelClass(): string
    {
        return Topic::class;
    }

    protected function routeBase(): string
    {
        return 'admin.topics';
    }

    protected function pluralTitle(): string
    {
        return 'موضوعات التحصيلي';
    }

    protected function singularTitle(): string
    {
        return 'موضوع';
    }

    /**
     * @return array<int, string>
     */
    private function chapters(): array
    {
        return Chapter::with('subject')->get()
            ->sortBy(fn (Chapter $c) => [$c->subject->sort_order, $c->sort_order])
            ->mapWithKeys(fn (Chapter $c) => [$c->id => $c->fullName()])->all();
    }

    protected function query(Request $request): Builder
    {
        return Topic::query()->with('chapter.subject')->withCount('contents')
            ->when($request->integer('chapter_id'), fn ($q, $id) => $q->where('chapter_id', $id))
            ->orderBy('chapter_id')->orderBy('sort_order')->orderBy('name');
    }

    protected function filters(): array
    {
        return [['name' => 'chapter_id', 'label' => 'الباب', 'options' => $this->chapters()]];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'chapter_id', 'label' => 'الباب', 'type' => 'select', 'options' => $this->chapters(), 'required' => true],
            ['name' => 'name', 'label' => 'اسم الموضوع', 'type' => 'text', 'required' => true],
            ['name' => 'sort_order', 'label' => 'الترتيب', 'type' => 'number'],
        ];
    }

    protected function columns(): array
    {
        return [
            'الموضوع' => fn (Topic $t) => $t->name,
            'الباب' => fn (Topic $t) => $t->chapter->fullName(),
            'المحتوى' => fn (Topic $t) => (string) $t->contents_count,
        ];
    }

    protected function defaults(Request $request): array
    {
        return ['chapter_id' => $request->integer('chapter_id') ?: null, 'sort_order' => 0];
    }

    public function store(TopicRequest $request): RedirectResponse
    {
        return $this->storeRecord($request->validated());
    }

    public function update(TopicRequest $request, int $topic): RedirectResponse
    {
        return $this->updateRecord($topic, $request->validated());
    }
}

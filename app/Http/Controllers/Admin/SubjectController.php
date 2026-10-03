<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SubjectRequest;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubjectController extends CatalogController
{
    protected function modelClass(): string
    {
        return Subject::class;
    }

    protected function routeBase(): string
    {
        return 'admin.subjects';
    }

    protected function pluralTitle(): string
    {
        return 'مواد التحصيلي';
    }

    protected function singularTitle(): string
    {
        return 'مادة';
    }

    protected function query(Request $request): Builder
    {
        return Subject::query()->withCount(['chapters', 'contents'])->orderBy('sort_order')->orderBy('name');
    }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'اسم المادة', 'type' => 'text', 'required' => true],
            ['name' => 'sort_order', 'label' => 'الترتيب', 'type' => 'number'],
            ['name' => 'is_active', 'label' => 'المادة ظاهرة للطالبات', 'type' => 'checkbox'],
        ];
    }

    protected function columns(): array
    {
        return [
            'المادة' => fn (Subject $s) => $s->name,
            'الأبواب' => fn (Subject $s) => (string) $s->chapters_count,
            'المحتوى' => fn (Subject $s) => (string) $s->contents_count,
            'الحالة' => fn (Subject $s) => $s->is_active ? 'ظاهرة' : 'مخفية',
        ];
    }

    protected function defaults(Request $request): array
    {
        return ['is_active' => true, 'sort_order' => 0];
    }

    public function store(SubjectRequest $request): RedirectResponse
    {
        return $this->storeRecord($request->validated());
    }

    public function update(SubjectRequest $request, int $subject): RedirectResponse
    {
        return $this->updateRecord($subject, $request->validated());
    }
}

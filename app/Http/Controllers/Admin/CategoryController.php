<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentSection;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CategoryController extends CatalogController
{
    protected function modelClass(): string
    {
        return Category::class;
    }

    protected function routeBase(): string
    {
        return 'admin.categories';
    }

    protected function pluralTitle(): string
    {
        return 'تصنيفات القدرات';
    }

    protected function singularTitle(): string
    {
        return 'تصنيف';
    }

    protected function description(): string
    {
        return 'مهارات القسم الكمي واللفظي. الترتيب يحدد ظهورها للطالبة.';
    }

    /**
     * @return array<string, string>
     */
    private function sections(): array
    {
        return [
            ContentSection::QUANTITATIVE->value => ContentSection::QUANTITATIVE->label(),
            ContentSection::VERBAL->value => ContentSection::VERBAL->label(),
        ];
    }

    protected function query(Request $request): Builder
    {
        return Category::query()->withCount('contents')
            ->when($request->input('section'), fn ($q, $section) => $q->where('section', $section))
            ->orderBy('section')->ordered();
    }

    protected function filters(): array
    {
        return [['name' => 'section', 'label' => 'القسم', 'options' => $this->sections()]];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'section', 'label' => 'القسم', 'type' => 'select', 'options' => $this->sections(), 'required' => true],
            ['name' => 'name', 'label' => 'اسم التصنيف', 'type' => 'text', 'required' => true],
            ['name' => 'description', 'label' => 'الوصف', 'type' => 'textarea'],
            ['name' => 'sort_order', 'label' => 'الترتيب', 'type' => 'number'],
            ['name' => 'is_active', 'label' => 'التصنيف ظاهر للطالبات', 'type' => 'checkbox'],
        ];
    }

    protected function columns(): array
    {
        return [
            'التصنيف' => fn (Category $c) => $c->name,
            'القسم' => fn (Category $c) => $c->section->label(),
            'الترتيب' => fn (Category $c) => (string) $c->sort_order,
            'المحتوى' => fn (Category $c) => (string) $c->contents_count,
            'الحالة' => fn (Category $c) => $c->is_active ? 'ظاهر' : 'مخفي',
        ];
    }

    protected function defaults(Request $request): array
    {
        return ['is_active' => true, 'section' => $request->input('section'), 'sort_order' => 0];
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        return $this->storeRecord($request->validated());
    }

    public function update(CategoryRequest $request, int $category): RedirectResponse
    {
        return $this->updateRecord($category, $request->validated());
    }
}

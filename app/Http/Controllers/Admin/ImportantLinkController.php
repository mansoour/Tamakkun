<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LinkCategory;
use App\Http\Requests\Admin\ImportantLinkRequest;
use App\Models\ImportantLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImportantLinkController extends CatalogController
{
    protected function modelClass(): string
    {
        return ImportantLink::class;
    }

    protected function routeBase(): string
    {
        return 'admin.links';
    }

    protected function pluralTitle(): string
    {
        return 'الروابط المهمة';
    }

    protected function singularTitle(): string
    {
        return 'رابط';
    }

    protected function description(): string
    {
        return 'أضيفي الروابط الرسمية بعد التحقق منها على الموقع الرسمي مباشرة. لا تُضاف روابط غير موثّقة.';
    }

    /**
     * @return array<string, string>
     */
    private function categories(): array
    {
        return collect(LinkCategory::cases())->mapWithKeys(fn (LinkCategory $c) => [$c->value => $c->label()])->all();
    }

    protected function query(Request $request): Builder
    {
        return ImportantLink::query()
            ->when($request->input('category'), fn ($q, $category) => $q->where('category', $category))
            ->orderBy('category')->orderBy('sort_order')->orderBy('title');
    }

    protected function filters(): array
    {
        return [['name' => 'category', 'label' => 'التصنيف', 'options' => $this->categories()]];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'title', 'label' => 'العنوان', 'type' => 'text', 'required' => true],
            ['name' => 'url', 'label' => 'الرابط (https)', 'type' => 'url', 'required' => true],
            ['name' => 'category', 'label' => 'التصنيف', 'type' => 'select', 'options' => $this->categories(), 'required' => true],
            ['name' => 'description', 'label' => 'الوصف', 'type' => 'textarea'],
            ['name' => 'sort_order', 'label' => 'الترتيب', 'type' => 'number'],
            ['name' => 'is_official', 'label' => 'مصدر رسمي', 'type' => 'checkbox', 'hint' => 'فعّليه فقط إذا كان الرابط تابعًا لجهة رسمية.'],
            ['name' => 'is_active', 'label' => 'الرابط ظاهر للطالبات', 'type' => 'checkbox'],
        ];
    }

    protected function columns(): array
    {
        return [
            'العنوان' => fn (ImportantLink $l) => $l->title,
            'التصنيف' => fn (ImportantLink $l) => $l->category->label(),
            'رسمي' => fn (ImportantLink $l) => $l->is_official ? 'نعم' : 'لا',
            'الحالة' => fn (ImportantLink $l) => $l->is_active ? 'ظاهر' : 'مخفي',
        ];
    }

    protected function defaults(Request $request): array
    {
        return ['is_active' => true, 'is_official' => false, 'sort_order' => 0, 'category' => $request->input('category')];
    }

    public function store(ImportantLinkRequest $request): RedirectResponse
    {
        return $this->storeRecord($request->validated());
    }

    public function update(ImportantLinkRequest $request, int $link): RedirectResponse
    {
        return $this->updateRecord($link, $request->validated());
    }
}

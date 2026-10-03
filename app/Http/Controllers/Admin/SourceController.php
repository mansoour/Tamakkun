<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SourceRequest;
use App\Models\Source;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SourceController extends CatalogController
{
    protected function modelClass(): string
    {
        return Source::class;
    }

    protected function routeBase(): string
    {
        return 'admin.sources';
    }

    protected function pluralTitle(): string
    {
        return 'المصادر';
    }

    protected function singularTitle(): string
    {
        return 'مصدر';
    }

    protected function description(): string
    {
        return 'جهات المحتوى التعليمي. لا يعني إدراج المصدر وجود شراكة رسمية، ولا يُضاف الموقع إلا بعد التحقق منه.';
    }

    protected function query(Request $request): Builder
    {
        return Source::query()->withCount('contents')->orderBy('name');
    }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'اسم المصدر', 'type' => 'text', 'required' => true],
            ['name' => 'website_url', 'label' => 'الموقع الرسمي (بعد التحقق فقط)', 'type' => 'url', 'hint' => 'اتركيه فارغًا إن لم يُتحقق من الرابط الرسمي.'],
            ['name' => 'description', 'label' => 'الوصف', 'type' => 'textarea'],
            ['name' => 'is_active', 'label' => 'المصدر نشط', 'type' => 'checkbox'],
        ];
    }

    protected function columns(): array
    {
        return [
            'المصدر' => fn (Source $s) => $s->name,
            'الموقع' => fn (Source $s) => $s->website_url ?? 'لم يُتحقق بعد',
            'المحتوى' => fn (Source $s) => (string) $s->contents_count,
            'الحالة' => fn (Source $s) => $s->is_active ? 'نشط' : 'موقوف',
        ];
    }

    protected function defaults(Request $request): array
    {
        return ['is_active' => true];
    }

    public function store(SourceRequest $request): RedirectResponse
    {
        return $this->storeRecord($request->validated());
    }

    public function update(SourceRequest $request, int $source): RedirectResponse
    {
        return $this->updateRecord($source, $request->validated());
    }
}

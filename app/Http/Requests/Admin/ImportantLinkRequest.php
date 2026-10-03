<?php

namespace App\Http\Requests\Admin;

use App\Enums\LinkCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportantLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Route middleware enforces links.manage.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'url' => ['required', 'url:https', 'max:2048'],
            'category' => ['required', Rule::enum(LinkCategory::class)],
            'is_official' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'between:0,1000'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['title' => 'العنوان', 'description' => 'الوصف', 'url' => 'الرابط', 'category' => 'التصنيف', 'sort_order' => 'الترتيب'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_official' => $this->boolean('is_official'),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => (int) $this->input('sort_order', 0),
        ]);
    }
}

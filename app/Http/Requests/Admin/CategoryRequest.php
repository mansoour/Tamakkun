<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'section' => ['required', Rule::in(['quantitative', 'verbal'])],
            'name' => ['required', 'string', 'max:255',
                Rule::unique('categories')->where('section', $this->input('section'))->ignore($this->route('category'))],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'between:0,1000'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['section' => 'القسم', 'name' => 'اسم التصنيف', 'description' => 'الوصف', 'sort_order' => 'الترتيب'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active'), 'sort_order' => (int) $this->input('sort_order', 0)]);
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChapterRequest extends FormRequest
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
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'name' => ['required', 'string', 'max:255',
                Rule::unique('chapters')->where('subject_id', $this->input('subject_id'))->ignore($this->route('chapter'))],
            'sort_order' => ['nullable', 'integer', 'between:0,1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['subject_id' => 'المادة', 'name' => 'اسم الباب', 'sort_order' => 'الترتيب'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['sort_order' => (int) $this->input('sort_order', 0)]);
    }
}

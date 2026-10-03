<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TopicRequest extends FormRequest
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
            'chapter_id' => ['required', 'integer', 'exists:chapters,id'],
            'name' => ['required', 'string', 'max:255',
                Rule::unique('topics')->where('chapter_id', $this->input('chapter_id'))->ignore($this->route('topic'))],
            'sort_order' => ['nullable', 'integer', 'between:0,1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['chapter_id' => 'الباب', 'name' => 'اسم الموضوع', 'sort_order' => 'الترتيب'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['sort_order' => (int) $this->input('sort_order', 0)]);
    }
}

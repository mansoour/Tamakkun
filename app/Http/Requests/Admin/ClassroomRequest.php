<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassroomRequest extends FormRequest
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
            'grade_id' => ['required', 'integer', 'exists:grades,id'],
            'name' => ['required', 'string', 'max:100',
                Rule::unique('classrooms')->where('grade_id', $this->input('grade_id'))->ignore($this->route('classroom'))],
            'sort_order' => ['nullable', 'integer', 'between:0,1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['grade_id' => 'الصف', 'name' => 'اسم الفصل', 'sort_order' => 'الترتيب'];
    }
}

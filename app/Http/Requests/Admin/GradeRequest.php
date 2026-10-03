<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradeRequest extends FormRequest
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
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:100',
                Rule::unique('grades')->where('academic_year_id', $this->input('academic_year_id'))->ignore($this->route('grade'))],
            'level' => ['nullable', 'integer', 'between:1,12'],
            'sort_order' => ['nullable', 'integer', 'between:0,1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['academic_year_id' => 'العام الدراسي', 'name' => 'اسم الصف', 'level' => 'المستوى', 'sort_order' => 'الترتيب'];
    }

    protected function passedValidation(): void
    {
        $this->merge(['sort_order' => (int) $this->input('sort_order', 0)]);
    }
}

<?php

namespace App\Http\Requests\Counselor;

use Illuminate\Foundation\Http\FormRequest;

class StoreCounselorNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('followUp', $this->route('student'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:5000'],
            'is_private' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['note' => 'الملاحظة'];
    }

    protected function prepareForValidation(): void
    {
        // Private unless the counselor explicitly shares it with the student.
        $this->merge(['is_private' => ! $this->boolean('share_with_student')]);
    }
}

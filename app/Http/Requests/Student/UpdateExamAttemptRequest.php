<?php

namespace App\Http\Requests\Student;

/**
 * Same rules as creating, except the exam type of an attempt cannot change.
 */
class UpdateExamAttemptRequest extends StoreExamAttemptRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('attempt'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), ['exam_type' => ['prohibited']]);
    }
}

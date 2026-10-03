<?php

namespace App\Http\Requests\Counselor;

use App\Enums\FollowUpStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFollowUpStatusRequest extends FormRequest
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
        return ['follow_up_status' => ['required', Rule::enum(FollowUpStatus::class)]];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['follow_up_status' => 'حالة المتابعة'];
    }
}

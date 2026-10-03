<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserStatus;
use App\Models\CounselorProfile;
use App\Support\Username;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CounselorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Route middleware enforces users.manage.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $counselor = $this->route('counselor');
        $creating = ! $counselor instanceof CounselorProfile;

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', ...Username::rules(), Rule::unique('users')->ignore($counselor?->user_id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($counselor?->user_id)],
            'password' => [$creating ? 'required' : 'nullable', 'string', Password::min(8), 'max:72'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:32'],
            'status' => [$creating ? 'required' : 'prohibited', Rule::enum(UserStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'الاسم', 'username' => 'اسم المستخدم', 'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور', 'school_id' => 'المدرسة', 'job_title' => 'المسمى الوظيفي',
            'phone' => 'رقم الجوال', 'status' => 'حالة الحساب',
        ];
    }
}

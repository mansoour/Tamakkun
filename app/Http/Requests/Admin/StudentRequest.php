<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Classroom;
use App\Models\StudentProfile;
use App\Models\User;
use App\Support\Username;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/**
 * Used for both creating and updating a student (password optional on update).
 */
class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student instanceof StudentProfile
            ? $this->user()->can('update', $student)
            : $this->user()->can('create', StudentProfile::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $student = $this->route('student');
        $creating = ! $student instanceof StudentProfile;

        return [
            'name' => ['required', 'string', 'max:255'],
            'student_code' => ['required', ...Username::rules(Username::CODE_MAX),
                Rule::unique('student_profiles')->ignore($student)],
            'username' => ['required', ...Username::rules(),
                Rule::unique('users')->ignore($student?->user_id)],
            'password' => [$creating ? 'required' : 'nullable', 'string', Password::min(8), 'max:72'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'counselor_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => [$creating ? 'required' : 'prohibited', Rule::enum(UserStatus::class)],
        ];
    }

    /**
     * Cross-field checks: the classroom and counselor must belong to the school.
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $schoolId = (int) $this->input('school_id');

            if ($this->filled('classroom_id')
                && Classroom::with('grade.academicYear')->find($this->input('classroom_id'))?->schoolId() !== $schoolId) {
                $validator->errors()->add('classroom_id', 'الفصل المختار لا يتبع هذه المدرسة.');
            }

            if ($this->filled('counselor_id')) {
                $isCounselor = User::role(RoleName::COUNSELOR->value)
                    ->whereKey($this->input('counselor_id'))
                    ->whereHas('counselorProfile', fn ($q) => $q->where('school_id', $schoolId))
                    ->exists();

                if (! $isCounselor) {
                    $validator->errors()->add('counselor_id', 'الموجهة المختارة لا تتبع هذه المدرسة.');
                }
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'الاسم', 'student_code' => 'رقم الطالبة', 'username' => 'اسم المستخدم',
            'password' => 'كلمة المرور', 'school_id' => 'المدرسة', 'classroom_id' => 'الفصل',
            'counselor_id' => 'الموجهة', 'status' => 'حالة الحساب',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('username') && $this->filled('student_code')) {
            $this->merge(['username' => $this->input('student_code')]);
        }
    }
}

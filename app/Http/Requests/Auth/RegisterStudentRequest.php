<?php

namespace App\Http\Requests\Auth;

use App\Models\Classroom;
use App\Services\StudentRegistrationService;
use App\Support\Username;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/**
 * Student self-registration. The username doubles as the student code, so
 * it must be free in both places.
 */
class RegisterStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Closed registration looks like a missing page, before any validation runs.
        abort_unless(app(StudentRegistrationService::class)->isOpen(), 404);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'username' => ['required', ...Username::rules(Username::CODE_MAX), 'min:3',
                Rule::unique('users', 'username'), Rule::unique('student_profiles', 'student_code')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8), 'max:72'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'grade_id' => ['required', 'integer', 'exists:grades,id'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
        ];
    }

    /**
     * The classroom must belong to the chosen grade and school, in a
     * school and year that are open for registration.
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $classroom = Classroom::with('grade.academicYear.school')->find($this->integer('classroom_id'));

            if (! $classroom
                || $classroom->grade_id !== $this->integer('grade_id')
                || $classroom->grade->academicYear->school_id !== $this->integer('school_id')
                || ! app(StudentRegistrationService::class)->isSelectable($classroom)) {
                $validator->errors()->add('classroom_id', 'اختاري الفصل من القائمة بعد اختيار المدرسة والصف.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'الاسم', 'username' => 'اسم المستخدم', 'email' => 'البريد الإلكتروني', 'password' => 'كلمة المرور',
            'school_id' => 'المدرسة', 'grade_id' => 'الصف', 'classroom_id' => 'الفصل',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.regex' => 'اسم المستخدم يقبل الحروف الإنجليزية والأرقام والرموز . - _ فقط.',
            'username.unique' => 'اسم المستخدم مستخدم من قبل. جرّبي اسمًا آخر.',
            'email.unique' => 'هذا البريد مسجّل من قبل. سجّلي الدخول أو استعيدي كلمة المرور.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim(preg_replace('/\s+/u', ' ', (string) $this->input('name'))),
            'username' => strtolower(trim((string) $this->input('username'))),
            'email' => trim((string) $this->input('email')) ?: null,
        ]);
    }
}

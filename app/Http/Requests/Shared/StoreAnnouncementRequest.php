<?php

namespace App\Http\Requests\Shared;

use App\Enums\AnnouncementAudience;
use App\Enums\PermissionName;
use App\Models\Classroom;
use App\Models\StudentProfile;
use App\Services\StudentRosterService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Admins (announcements.manage-all) may address everyone, any class or any
 * student. Counselors (announcements.send) may address their own students,
 * a class containing their students, or one of their students.
 */
class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermissionName::ANNOUNCE_TO_ALL->value)
            || $this->user()->can(PermissionName::SEND_ANNOUNCEMENTS->value);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $allowed = $this->user()->can(PermissionName::ANNOUNCE_TO_ALL->value)
            ? [AnnouncementAudience::ALL, AnnouncementAudience::CLASSROOM, AnnouncementAudience::STUDENT]
            : [AnnouncementAudience::MY_STUDENTS, AnnouncementAudience::CLASSROOM, AnnouncementAudience::STUDENT];

        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:3000'],
            'audience' => ['required', Rule::in(array_map(fn ($a) => $a->value, $allowed))],
            'target_id' => ['nullable', 'required_if:audience,classroom,student', 'integer'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at', 'after:now'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $id = $this->integer('target_id');
            $mine = app(StudentRosterService::class)->scopeFor($this->user());

            $valid = match ($this->input('audience')) {
                'classroom' => $this->user()->can(PermissionName::ANNOUNCE_TO_ALL->value)
                    ? Classroom::whereKey($id)->exists()
                    : (clone $mine)->where('classroom_id', $id)->exists(),
                'student' => $this->user()->can(PermissionName::ANNOUNCE_TO_ALL->value)
                    ? StudentProfile::where('user_id', $id)->exists()
                    : (clone $mine)->where('user_id', $id)->exists(),
                default => true,
            };

            if (! $valid) {
                $validator->errors()->add('target_id', 'لا يمكنك الإرسال إلى هذا المستهدف.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'العنوان', 'body' => 'النص', 'audience' => 'المستهدفات', 'target_id' => 'المستهدف',
            'starts_at' => 'بداية العرض', 'ends_at' => 'نهاية العرض',
        ];
    }
}

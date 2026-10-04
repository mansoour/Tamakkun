<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\Classroom;
use App\Models\CounselorProfile;
use App\Models\School;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\DB;

/**
 * Student self-registration (setting `student_registration`):
 * - open: the account is active at once and the student is signed in;
 * - approval: the account is pending until an admin activates it;
 * - closed: the register page does not exist.
 *
 * The student picks her school, grade and classroom from the school
 * structure; only active schools and their current academic year are offered.
 */
class StudentRegistrationService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly SchoolMembershipService $membership,
        private readonly AuditLogger $audit,
    ) {}

    public function mode(): string
    {
        $mode = $this->settings->get('student_registration');

        return in_array($mode, ['open', 'approval', 'closed'], true) ? $mode : 'closed';
    }

    public function isOpen(): bool
    {
        return $this->mode() !== 'closed';
    }

    /**
     * Schools → grades → classrooms a student may register into.
     *
     * @return list<array{id: int, name: string, grades: list<array{id: int, name: string, classrooms: list<array{id: int, name: string}>}>}>
     */
    public function options(): array
    {
        return School::query()->where('is_active', true)->orderBy('name')
            ->with(['academicYears' => fn ($q) => $q->where('is_current', true)
                ->with(['grades' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')
                    ->with(['classrooms' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')])])])
            ->get()
            ->map(fn (School $school) => [
                'id' => $school->id,
                'name' => $school->name.($school->city ? " – {$school->city}" : ''),
                'grades' => $school->academicYears->flatMap->grades
                    ->filter(fn ($grade) => $grade->classrooms->isNotEmpty())
                    ->map(fn ($grade) => [
                        'id' => $grade->id,
                        'name' => $grade->name,
                        'classrooms' => $grade->classrooms->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()->all(),
                    ])->values()->all(),
            ])
            ->filter(fn (array $school) => $school['grades'] !== [])
            ->values()->all();
    }

    /**
     * Whether a classroom is one a student may pick (active school, current year).
     */
    public function isSelectable(Classroom $classroom): bool
    {
        $year = $classroom->grade?->academicYear;

        return $year !== null && $year->is_current && (bool) $year->school?->is_active;
    }

    /**
     * @param  array{name: string, username: string, email?: string|null, password: string, classroom_id: int}  $data
     */
    public function register(array $data): StudentProfile
    {
        return DB::transaction(function () use ($data) {
            $classroom = Classroom::with('grade.academicYear')->findOrFail($data['classroom_id']);
            $schoolId = $classroom->grade->academicYear->school_id;

            $profile = $this->membership->createStudent([
                'name' => $data['name'],
                'username' => $data['username'],
                'student_code' => $data['username'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'school_id' => $schoolId,
                'classroom_id' => $classroom->id,
                'counselor_id' => $this->soleCounselorId($schoolId),
                'status' => $this->mode() === 'open' ? UserStatus::ACTIVE : UserStatus::PENDING,
            ]);

            // She chose this password herself, so there is nothing to replace.
            $profile->user->forceFill(['must_change_password' => false])->save();

            $this->audit->record('student.self_registered', $profile, null, [
                'username' => $profile->user->username,
                'classroom_id' => $classroom->id,
                'status' => $profile->user->status->value,
            ]);

            return $profile;
        });
    }

    /**
     * A school with exactly one active counselor gets new students assigned to her.
     */
    private function soleCounselorId(int $schoolId): ?int
    {
        $ids = CounselorProfile::where('school_id', $schoolId)
            ->whereHas('user', fn ($q) => $q->where('status', UserStatus::ACTIVE))
            ->limit(2)->pluck('user_id');

        return $ids->count() === 1 ? $ids->first() : null;
    }
}

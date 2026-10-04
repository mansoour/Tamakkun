<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\CounselorProfile;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Creates and updates student/counselor accounts together with their
 * profiles and school membership. Input is already validated by a Form
 * Request (or by StudentImportService).
 */
class SchoolMembershipService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly AccountActivation $activation,
    ) {}

    /**
     * @param  array{name: string, username?: string|null, student_code: string, email?: string|null, password: string, school_id: int, classroom_id?: int|null, counselor_id?: int|null, status?: UserStatus|string|null}  $data
     */
    public function createStudent(array $data): StudentProfile
    {
        return DB::transaction(function () use ($data) {
            $user = $this->createUser([
                'name' => $data['name'],
                'username' => ($data['username'] ?? null) ?: $data['student_code'],
                'email' => ($data['email'] ?? null) ?: null,
                'password' => $data['password'],
            ], RoleName::STUDENT, $data['status'] ?? UserStatus::ACTIVE);

            $profile = $user->studentProfile()->create(Arr::only($data, ['school_id', 'classroom_id', 'counselor_id', 'student_code']));

            $this->audit->record('student.created', $profile, null, $this->studentSnapshot($profile));

            return $profile;
        });
    }

    /**
     * @param  array{name: string, username: string, student_code: string, school_id: int, classroom_id?: int|null, counselor_id?: int|null, password?: string|null}  $data
     */
    public function updateStudent(StudentProfile $profile, array $data): StudentProfile
    {
        return DB::transaction(function () use ($profile, $data) {
            $before = $this->studentSnapshot($profile);

            $this->updateUser($profile->user, Arr::only($data, ['name', 'username', 'password']));
            $profile->update(Arr::only($data, ['school_id', 'classroom_id', 'counselor_id', 'student_code']));

            $after = $this->studentSnapshot($profile->refresh());

            if ($before['counselor_id'] !== $after['counselor_id']) {
                $this->audit->record('student.assigned_to_counselor', $profile,
                    ['counselor_id' => $before['counselor_id']], ['counselor_id' => $after['counselor_id']]);
            }

            $this->recordChanges('student.updated', $profile, $before, $after, isset($data['password']) && $data['password'] !== '');

            return $profile;
        });
    }

    /**
     * @param  array{name: string, username: string, email?: string|null, password: string, school_id: int, job_title?: string|null, phone?: string|null, status?: UserStatus|string|null}  $data
     */
    public function createCounselor(array $data): CounselorProfile
    {
        return DB::transaction(function () use ($data) {
            $user = $this->createUser(
                Arr::only($data, ['name', 'username', 'email', 'password']),
                RoleName::COUNSELOR,
                $data['status'] ?? UserStatus::ACTIVE,
            );

            $profile = $user->counselorProfile()->create(Arr::only($data, ['school_id', 'job_title', 'phone']));

            $this->audit->record('counselor.created', $profile, null, $this->counselorSnapshot($profile));

            return $profile;
        });
    }

    /**
     * @param  array{name: string, username: string, email?: string|null, school_id: int, job_title?: string|null, phone?: string|null, password?: string|null}  $data
     */
    public function updateCounselor(CounselorProfile $profile, array $data): CounselorProfile
    {
        return DB::transaction(function () use ($profile, $data) {
            $before = $this->counselorSnapshot($profile);

            $this->updateUser($profile->user, Arr::only($data, ['name', 'username', 'email', 'password']));
            $profile->update(Arr::only($data, ['school_id', 'job_title', 'phone']));

            $this->recordChanges('counselor.updated', $profile, $before, $this->counselorSnapshot($profile->refresh()),
                isset($data['password']) && $data['password'] !== '');

            return $profile;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createUser(array $attributes, RoleName $role, UserStatus|string $status): User
    {
        $user = User::create($attributes);
        // The admin chose this password, so the user must replace it (see docs/security.md).
        $user->forceFill(['status' => UserStatus::PENDING, 'must_change_password' => true])->save();
        $user->assignRole($role->value);

        $this->audit->record('user.created', $user, null, [
            'username' => $user->username,
            'role' => $role->value,
        ]);

        $status = $status instanceof UserStatus ? $status : UserStatus::from($status);

        if ($status !== UserStatus::PENDING) {
            $this->activation->changeStatus($user, $status);
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function updateUser(User $user, array $attributes): void
    {
        if (blank($attributes['password'] ?? null)) {
            unset($attributes['password']);
        } else {
            // An admin-set password must be replaced by the user at next login.
            $user->forceFill(['must_change_password' => true]);
        }

        $user->update($attributes);
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    private function recordChanges(string $action, StudentProfile|CounselorProfile $profile, array $before, array $after, bool $passwordChanged): void
    {
        $changedKeys = array_keys(array_diff_assoc(array_map('strval', $after), array_map('strval', $before)));

        if ($passwordChanged) {
            // Never store the password itself, only the fact it changed.
            $before['password'] = '***';
            $after['password'] = 'changed';
            $changedKeys[] = 'password';
        }

        if ($changedKeys === []) {
            return;
        }

        $this->audit->record($action, $profile, Arr::only($before, $changedKeys), Arr::only($after, $changedKeys));
    }

    /**
     * @return array<string, mixed>
     */
    private function studentSnapshot(StudentProfile $profile): array
    {
        return [
            'name' => $profile->user->name,
            'username' => $profile->user->username,
            'student_code' => $profile->student_code,
            'school_id' => $profile->school_id,
            'classroom_id' => $profile->classroom_id,
            'counselor_id' => $profile->counselor_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function counselorSnapshot(CounselorProfile $profile): array
    {
        return [
            'name' => $profile->user->name,
            'username' => $profile->user->username,
            'email' => $profile->user->email,
            'school_id' => $profile->school_id,
            'job_title' => $profile->job_title,
            'phone' => $profile->phone,
        ];
    }
}

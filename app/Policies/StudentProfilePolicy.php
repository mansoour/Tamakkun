<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\StudentProfile;
use App\Models\User;

/**
 * Record-level access to students.
 *
 * - students.view-all: any student (admins).
 * - students.view-assigned: only students whose counselor_id is the user.
 * - users.manage: create/update.
 */
class StudentProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::VIEW_ALL_STUDENTS->value)
            || $user->can(PermissionName::VIEW_ASSIGNED_STUDENTS->value);
    }

    public function view(User $user, StudentProfile $student): bool
    {
        if ($user->can(PermissionName::VIEW_ALL_STUDENTS->value)) {
            return true;
        }

        return $user->can(PermissionName::VIEW_ASSIGNED_STUDENTS->value)
            && $student->counselor_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::MANAGE_USERS->value);
    }

    public function update(User $user, StudentProfile $student): bool
    {
        return $user->can(PermissionName::MANAGE_USERS->value);
    }
}

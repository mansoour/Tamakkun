<?php

namespace App\Policies;

use App\Models\ExamAttempt;
use App\Models\User;

/**
 * A student manages only her own attempts. Others (counselors, admins)
 * may view them when they may view that student; they cannot edit them.
 */
class ExamAttemptPolicy
{
    public function view(User $user, ExamAttempt $attempt): bool
    {
        if ($attempt->student_id === $user->id) {
            return true;
        }

        $profile = $attempt->student->studentProfile;

        return $profile !== null && $user->can('view', $profile);
    }

    public function update(User $user, ExamAttempt $attempt): bool
    {
        return $attempt->student_id === $user->id;
    }

    public function delete(User $user, ExamAttempt $attempt): bool
    {
        return $attempt->student_id === $user->id;
    }
}

<?php

namespace App\Policies;

use App\Models\StudentAlert;
use App\Models\User;

class StudentAlertPolicy
{
    public function update(User $user, StudentAlert $alert): bool
    {
        $profile = $alert->student->studentProfile;

        return $profile !== null && $user->can('followUp', $profile);
    }
}

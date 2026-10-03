<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Classroom;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;

/**
 * Aggregated numbers for dashboards. Every figure here is a real count;
 * nothing is estimated or faked.
 */
class DashboardMetricsService
{
    /**
     * @return array{schools: int, classrooms: int, students: int, counselors: int, pending: int, unassigned: int}
     */
    public function adminOverview(): array
    {
        return [
            'schools' => School::count(),
            'classrooms' => Classroom::count(),
            'students' => User::role(RoleName::STUDENT->value)->count(),
            'counselors' => User::role(RoleName::COUNSELOR->value)->count(),
            'pending' => User::where('status', UserStatus::PENDING)->count(),
            'unassigned' => StudentProfile::whereNull('counselor_id')->count(),
        ];
    }
}

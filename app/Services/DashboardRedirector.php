<?php

namespace App\Services;

use App\Enums\PermissionName;
use App\Models\User;

/**
 * Decides which area a user lands on after signing in.
 *
 * The decision is permission-driven: the first area the user may access,
 * from the broadest (admin) to the narrowest (student), wins.
 */
class DashboardRedirector
{
    /**
     * @var array<string, string> permission => route name
     */
    private const AREAS = [
        PermissionName::ACCESS_ADMIN_AREA->value => 'admin.dashboard',
        PermissionName::ACCESS_COUNSELOR_AREA->value => 'counselor.dashboard',
        PermissionName::ACCESS_STUDENT_AREA->value => 'student.dashboard',
    ];

    public function routeFor(User $user): ?string
    {
        foreach (self::AREAS as $permission => $route) {
            if ($user->can($permission)) {
                return $route;
            }
        }

        return null;
    }
}

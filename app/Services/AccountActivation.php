<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * The only place that changes `users.status`. Every change is audited.
 * A user signed in elsewhere is signed out on their next request by the
 * EnsureUserIsActive middleware.
 */
class AccountActivation
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function activate(User $user): void
    {
        $this->changeStatus($user, UserStatus::ACTIVE);
    }

    public function changeStatus(User $user, UserStatus $status): void
    {
        if ($status !== UserStatus::ACTIVE && Auth::id() === $user->id) {
            throw new InvalidArgumentException('لا يمكنك إيقاف حسابك بنفسك.');
        }

        $old = $user->status;

        if ($old === $status) {
            return;
        }

        $user->forceFill(['status' => $status])->save();

        $this->audit->record(
            'user.status_changed',
            $user,
            ['status' => $old?->value],
            ['status' => $status->value],
        );
    }
}

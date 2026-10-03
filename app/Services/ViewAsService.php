<?php

namespace App\Services;

use App\Enums\PermissionName;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use InvalidArgumentException;

/**
 * Read-only "view as user" (brief §19).
 *
 * The admin stays logged in as themselves; the session only remembers whom
 * they are viewing. App\Http\Middleware\ViewAsUser swaps the user for GET
 * requests, blocks every write and rolls back anything a page might store.
 * This is not impersonation: nothing can be done on the user's behalf.
 */
class ViewAsService
{
    public const SESSION_KEY = 'view_as';

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Admins and anyone else who can open the admin area cannot be viewed,
     * so this can never be used to look at a more privileged account.
     */
    public function canView(User $admin, User $target): bool
    {
        return $admin->can(PermissionName::VIEW_AS_USER->value)
            && ! $admin->is($target)
            && $target->isActive()
            && ! $target->can(PermissionName::ACCESS_ADMIN_AREA->value);
    }

    public function start(Session $session, User $admin, User $target): void
    {
        if (! $this->canView($admin, $target)) {
            throw new InvalidArgumentException('لا يمكن عرض المنصة كما يراها هذا الحساب.');
        }

        $session->put(self::SESSION_KEY, ['admin_id' => $admin->id, 'user_id' => $target->id]);

        $this->audit->record('user.view-as-started', $target, null, ['username' => $target->username]);
    }

    /**
     * The user being viewed, or null when the session is not (validly) viewing anyone.
     */
    public function target(Session $session, User $admin): ?User
    {
        $state = $session->get(self::SESSION_KEY);

        if (! is_array($state) || ($state['admin_id'] ?? null) !== $admin->id) {
            return null;
        }

        $target = User::find($state['user_id'] ?? null);

        return $target && $this->canView($admin, $target) ? $target : null;
    }

    public function stop(Session $session): void
    {
        $state = $session->pull(self::SESSION_KEY);

        if (is_array($state) && ($target = User::find($state['user_id'] ?? null))) {
            $this->audit->record('user.view-as-ended', $target);
        }
    }
}

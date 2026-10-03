<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Records important admin/counselor actions in `audit_logs`.
 *
 * Action names use dotted, past-tense identifiers such as
 * `settings.updated` or `user.disabled` (see docs/security.md).
 */
class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function record(string $action, ?Model $auditable = null, ?array $oldValues = null, ?array $newValues = null): AuditLog
    {
        // Resolved per call so long-running processes never reuse a stale request.
        $request = request();

        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}

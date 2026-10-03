<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Records meaningful student actions in `activity_logs` (not admin actions —
 * those belong in AuditLogger). Keep metadata minimal.
 */
class ActivityLogger
{
    /**
     * @param  array<string, scalar|null>  $metadata
     */
    public function log(User $user, ActivityEvent $event, ?Model $subject = null, array $metadata = []): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $user->id,
            'event_type' => $event,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata ?: null,
            'created_at' => now(),
        ]);
    }
}

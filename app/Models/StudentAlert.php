<?php

namespace App\Models;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\AlertType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Automatic follow-up alert. Created and auto-resolved by
 * App\Services\StudentAlertService; counselors acknowledge or resolve it.
 */
#[Fillable(['student_id', 'alert_type', 'context_key', 'severity', 'title', 'message', 'status', 'generated_at', 'resolved_at', 'resolved_by'])]
class StudentAlert extends Model
{
    protected function casts(): array
    {
        return [
            'alert_type' => AlertType::class,
            'severity' => AlertSeverity::class,
            'status' => AlertStatus::class,
            'generated_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeUnresolved(Builder $query): void
    {
        $query->whereIn('status', AlertStatus::unresolvedValues());
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}

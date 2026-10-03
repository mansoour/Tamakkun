<?php

namespace App\Models;

use App\Enums\ProgressStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One student's progress on one content item. Change it only through
 * App\Services\ContentCompletionService.
 */
#[Table('student_content_progress')]
#[Fillable(['student_id', 'content_id', 'status', 'progress_percentage', 'started_at', 'completed_at', 'last_viewed_at'])]
class StudentContentProgress extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ProgressStatus::class,
            'progress_percentage' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_viewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * @return BelongsTo<Content, $this>
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}

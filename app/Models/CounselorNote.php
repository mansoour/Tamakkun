<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A counselor's note about a student. Private notes are never shown to the
 * student; non-private notes appear on her dashboard.
 */
#[Fillable(['student_id', 'counselor_id', 'note', 'is_private'])]
class CounselorNote extends Model
{
    protected function casts(): array
    {
        return ['is_private' => 'boolean'];
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
    public function counselor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }
}

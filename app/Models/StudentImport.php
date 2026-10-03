<?php

namespace App\Models;

use App\Enums\ImportStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One CSV upload. `payload` holds the validated rows (including initial
 * passwords) encrypted at rest, and is cleared once the import finishes.
 */
#[Fillable(['uploaded_by', 'original_filename', 'status', 'total_rows', 'imported_rows', 'payload', 'row_errors', 'error_message', 'completed_at'])]
#[Hidden(['payload'])]
class StudentImport extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ImportStatus::class,
            'payload' => 'encrypted:array',
            'row_errors' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function hasErrors(): bool
    {
        return ! empty($this->row_errors);
    }
}

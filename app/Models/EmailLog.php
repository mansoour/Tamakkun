<?php

namespace App\Models;

use App\Enums\EmailStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'recipient', 'subject', 'template', 'status', 'provider_message_id', 'error_message', 'sent_at'])]
class EmailLog extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'status' => EmailStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

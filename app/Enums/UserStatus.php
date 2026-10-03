<?php

namespace App\Enums;

enum UserStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case DISABLED = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'بانتظار التفعيل',
            self::ACTIVE => 'نشط',
            self::SUSPENDED => 'موقوف مؤقتًا',
            self::DISABLED => 'معطّل',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::ACTIVE => 'success',
            self::SUSPENDED => 'warning',
            self::DISABLED => 'danger',
        };
    }

    /**
     * Whether a user with this status is allowed to sign in and use the platform.
     */
    public function canSignIn(): bool
    {
        return $this === self::ACTIVE;
    }
}

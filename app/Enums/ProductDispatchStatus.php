<?php

namespace App\Enums;

enum ProductDispatchStatus: string
{
    case PENDING_VERIFICATION = 'pending_verification';
    case ACCEPTED = 'accepted';
    case RECEIVED = 'received';
    case REJECTED = 'rejected';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}

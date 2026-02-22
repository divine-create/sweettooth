<?php

namespace App\Enums;

enum ItemRequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case PARTIALLY_DISPATCHED = 'partially_dispatched';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}

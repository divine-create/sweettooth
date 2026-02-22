<?php

namespace App\Enums;

enum ProductionRequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case IN_PROGRESS = 'in_progress';
    case QUALITY_CHECK = 'quality_check';
    case COMPLETED = 'completed';
    case DISPATCHED = 'dispatched';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}

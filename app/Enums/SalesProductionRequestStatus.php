<?php

namespace App\Enums;

enum SalesProductionRequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED_BY_PRODUCTION = 'approved_by_production';
    case MATERIALS_REQUESTED = 'materials_requested';
    case MATERIALS_APPROVED = 'materials_approved';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case DISPATCHED = 'dispatched';
    case RECEIVED_BY_SALES = 'received_by_sales';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }

    public function canTransitionTo(self $target): bool
    {
        if ($this === $target) {
            return true;
        }

        return match ($this) {
            self::PENDING => in_array($target, [
                self::APPROVED_BY_PRODUCTION,
                self::REJECTED,
                self::CANCELLED,
            ], true),
            self::APPROVED_BY_PRODUCTION => in_array($target, [
                self::MATERIALS_REQUESTED,
                self::REJECTED,
                self::CANCELLED,
            ], true),
            self::MATERIALS_REQUESTED => in_array($target, [
                self::MATERIALS_APPROVED,
                self::REJECTED,
                self::CANCELLED,
            ], true),
            self::MATERIALS_APPROVED => in_array($target, [
                self::PROCESSING,
                self::REJECTED,
                self::CANCELLED,
            ], true),
            self::PROCESSING => in_array($target, [
                self::COMPLETED,
                self::REJECTED,
            ], true),
            self::COMPLETED => in_array($target, [
                self::DISPATCHED,
            ], true),
            self::DISPATCHED => in_array($target, [
                self::RECEIVED_BY_SALES,
            ], true),
            self::RECEIVED_BY_SALES,
            self::REJECTED,
            self::CANCELLED => false,
        };
    }

    public function canReject(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::APPROVED_BY_PRODUCTION,
            self::MATERIALS_REQUESTED,
            self::MATERIALS_APPROVED,
            self::PROCESSING,
        ], true);
    }

    public function canCancel(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::APPROVED_BY_PRODUCTION,
            self::MATERIALS_REQUESTED,
            self::MATERIALS_APPROVED,
        ], true);
    }
}


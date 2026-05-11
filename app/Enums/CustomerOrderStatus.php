<?php

namespace App\Enums;

enum CustomerOrderStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case IN_PRODUCTION = 'in_production';
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

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
                self::APPROVED,
                self::REJECTED,
                self::CANCELLED,
            ], true),
            self::APPROVED => in_array($target, [
                self::IN_PRODUCTION,
                self::REJECTED,
                self::CANCELLED,
            ], true),
            self::IN_PRODUCTION => in_array($target, [
                self::COMPLETED,
            ], true),
            self::COMPLETED,
            self::REJECTED,
            self::CANCELLED => false,
        };
    }

    public function canApprove(): bool
    {
        return $this === self::PENDING;
    }

    public function canReject(): bool
    {
        return in_array($this, [self::PENDING, self::APPROVED], true);
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::PENDING, self::APPROVED], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::IN_PRODUCTION => 'In Production',
            self::COMPLETED => 'Completed',
            self::REJECTED => 'Rejected',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
            self::APPROVED => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
            self::IN_PRODUCTION => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
            self::COMPLETED => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            self::REJECTED => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            self::CANCELLED => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400',
        };
    }
}

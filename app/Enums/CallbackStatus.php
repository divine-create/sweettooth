<?php

namespace App\Enums;

enum CallbackStatus: string
{
    case PENDING = 'pending';
    case APPROVED_BY_PRODUCTION = 'approved_by_production';
    case RECEIVED_BY_PRODUCTION = 'received_by_production';
    case COMPLETED = 'completed';
    case APPROVED_BY_INVENTORY = 'approved_by_inventory';
    case REJECTED = 'rejected';

    /**
     * Check if a status transition is valid
     *
     * @param CallbackStatus $target
     * @return bool
     */
    public function canTransitionTo(CallbackStatus $target): bool
    {
        return match ($this) {
            self::PENDING => in_array($target, [
                self::APPROVED_BY_PRODUCTION,
                self::APPROVED_BY_INVENTORY,
                self::REJECTED,
            ]),
            self::APPROVED_BY_PRODUCTION => in_array($target, [
                self::RECEIVED_BY_PRODUCTION,
            ]),
            self::RECEIVED_BY_PRODUCTION => in_array($target, [
                self::COMPLETED,
            ]),
            self::APPROVED_BY_INVENTORY => in_array($target, [
                self::COMPLETED,
            ]),
            default => false,
        };
    }

    /**
     * Get human-readable label for the status
     *
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Approval',
            self::APPROVED_BY_PRODUCTION => 'Approved by Production',
            self::RECEIVED_BY_PRODUCTION => 'Received by Production',
            self::COMPLETED => 'Completed',
            self::APPROVED_BY_INVENTORY => 'Approved by Inventory',
            self::REJECTED => 'Rejected',
        };
    }

    /**
     * Get badge color for UI display
     *
     * @return string
     */
    public function getBadgeColor(): string
    {
        return match ($this) {
            self::PENDING => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            self::APPROVED_BY_PRODUCTION => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            self::RECEIVED_BY_PRODUCTION => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
            self::APPROVED_BY_INVENTORY => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
            self::COMPLETED => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            self::REJECTED => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        };
    }
}

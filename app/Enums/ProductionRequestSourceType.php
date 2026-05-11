<?php

namespace App\Enums;

enum ProductionRequestSourceType: string
{
    case SALES_DEMAND = 'SALES_DEMAND';
    case RESTOCK = 'RESTOCK';
    case MANUAL = 'MANUAL';
    case CUSTOMER_ORDER = 'CUSTOMER_ORDER';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}

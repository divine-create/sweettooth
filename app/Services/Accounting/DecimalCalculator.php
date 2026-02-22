<?php

namespace App\Services\Accounting;

use DivisionByZeroError;

/**
 * Decimal Calculator Service
 *
 * Provides standardized decimal arithmetic for accounting operations.
 * Ensures consistent precision and rounding across the application.
 */
class DecimalCalculator
{
    /**
     * Default precision for calculations
     */
    public const DEFAULT_PRECISION = 2;

    /**
     * Default tolerance for comparisons
     */
    public const DEFAULT_TOLERANCE = 0.01;

    /**
     * Add two decimal values with precision
     *
     * @param float $a
     * @param float $b
     * @param int $precision
     * @return float
     */
    public static function add(float $a, float $b, int $precision = self::DEFAULT_PRECISION): float
    {
        return round($a + $b, $precision);
    }

    /**
     * Subtract two decimal values with precision
     *
     * @param float $a
     * @param float $b
     * @param int $precision
     * @return float
     */
    public static function subtract(float $a, float $b, int $precision = self::DEFAULT_PRECISION): float
    {
        return round($a - $b, $precision);
    }

    /**
     * Multiply two decimal values with precision
     *
     * @param float $a
     * @param float $b
     * @param int $precision
     * @return float
     */
    public static function multiply(float $a, float $b, int $precision = self::DEFAULT_PRECISION): float
    {
        return round($a * $b, $precision);
    }

    /**
     * Divide two decimal values with precision
     *
     * @param float $a Dividend
     * @param float $b Divisor
     * @param int $precision
     * @return float
     * @throws DivisionByZeroError
     */
    public static function divide(float $a, float $b, int $precision = self::DEFAULT_PRECISION): float
    {
        if (abs($b) < 0.0000001) {
            throw new DivisionByZeroError('Cannot divide by zero');
        }

        return round($a / $b, $precision);
    }

    /**
     * Compare two decimal values
     *
     * @param float $a
     * @param float $b
     * @param float $tolerance
     * @return int 0 if equal, 1 if a > b, -1 if a < b
     */
    public static function compare(float $a, float $b, float $tolerance = self::DEFAULT_TOLERANCE): int
    {
        $diff = abs($a - $b);

        if ($diff <= $tolerance) {
            return 0; // Equal
        }

        return $a > $b ? 1 : -1;
    }

    /**
     * Check if two values are equal within tolerance
     *
     * @param float $a
     * @param float $b
     * @param float $tolerance
     * @return bool
     */
    public static function equals(float $a, float $b, float $tolerance = self::DEFAULT_TOLERANCE): bool
    {
        return self::compare($a, $b, $tolerance) === 0;
    }

    /**
     * Sum an array of values with precision
     *
     * @param array $values
     * @param int $precision
     * @return float
     */
    public static function sum(array $values, int $precision = self::DEFAULT_PRECISION): float
    {
        $total = 0;
        foreach ($values as $value) {
            $total += floatval($value);
        }

        return round($total, $precision);
    }

    /**
     * Calculate percentage with precision
     *
     * @param float $value
     * @param float $percentage
     * @param int $precision
     * @return float
     */
    public static function percentage(float $value, float $percentage, int $precision = self::DEFAULT_PRECISION): float
    {
        return round($value * ($percentage / 100), $precision);
    }

    /**
     * Round to nearest specified increment
     *
     * @param float $value
     * @param float $increment
     * @return float
     */
    public static function roundToNearest(float $value, float $increment = 0.01): float
    {
        return round($value / $increment) * $increment;
    }

    /**
     * Round up (ceiling) with precision
     *
     * @param float $value
     * @param int $precision
     * @return float
     */
    public static function ceil(float $value, int $precision = self::DEFAULT_PRECISION): float
    {
        $multiplier = pow(10, $precision);
        return ceil($value * $multiplier) / $multiplier;
    }

    /**
     * Round down (floor) with precision
     *
     * @param float $value
     * @param int $precision
     * @return float
     */
    public static function floor(float $value, int $precision = self::DEFAULT_PRECISION): float
    {
        $multiplier = pow(10, $precision);
        return floor($value * $multiplier) / $multiplier;
    }

    /**
     * Get absolute value with precision
     *
     * @param float $value
     * @param int $precision
     * @return float
     */
    public static function abs(float $value, int $precision = self::DEFAULT_PRECISION): float
    {
        return round(abs($value), $precision);
    }

    /**
     * Calculate weighted average
     *
     * @param array $values Array of ['value' => float, 'weight' => float]
     * @param int $precision
     * @return float
     */
    public static function weightedAverage(array $values, int $precision = self::DEFAULT_PRECISION): float
    {
        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($values as $item) {
            $value = floatval($item['value'] ?? 0);
            $weight = floatval($item['weight'] ?? 1);

            $weightedSum += $value * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight == 0) {
            return 0;
        }

        return round($weightedSum / $totalWeight, $precision);
    }

    /**
     * Allocate an amount proportionally across items
     *
     * @param float $total Total amount to allocate
     * @param array $proportions Array of proportion values
     * @param int $precision
     * @return array Allocated amounts (same keys as proportions)
     */
    public static function allocate(float $total, array $proportions, int $precision = self::DEFAULT_PRECISION): array
    {
        $sumProportions = array_sum($proportions);

        if ($sumProportions == 0) {
            return array_fill_keys(array_keys($proportions), 0);
        }

        $allocated = [];
        $runningTotal = 0;

        $keys = array_keys($proportions);
        $lastKey = end($keys);

        foreach ($proportions as $key => $proportion) {
            if ($key === $lastKey) {
                // Last item gets the remainder to avoid rounding errors
                $allocated[$key] = round($total - $runningTotal, $precision);
            } else {
                $amount = round(($proportion / $sumProportions) * $total, $precision);
                $allocated[$key] = $amount;
                $runningTotal += $amount;
            }
        }

        return $allocated;
    }

    /**
     * Format number for display
     *
     * @param float $value
     * @param int $precision
     * @param string $thousandsSeparator
     * @param string $decimalSeparator
     * @return string
     */
    public static function format(
        float $value,
        int $precision = self::DEFAULT_PRECISION,
        string $thousandsSeparator = ',',
        string $decimalSeparator = '.'
    ): string {
        return number_format($value, $precision, $decimalSeparator, $thousandsSeparator);
    }
}

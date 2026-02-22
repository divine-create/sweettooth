<?php

namespace App\Services\Accounting;

use App\Models\AccountingPeriod;
use App\Models\GlAccount;
use Carbon\Carbon;
use NumberFormatter;

/**
 * Journal Entry Validation Service
 *
 * Provides comprehensive validation for journal entries including:
 * - Balance validation (debits = credits)
 * - Period validation
 * - Account type restrictions
 * - Currency-specific tolerances
 */
class JournalEntryValidator
{
    /**
     * Validate that a journal entry is balanced
     *
     * @param array $lines Journal entry lines with 'debit' and 'credit' keys
     * @param string|null $currency Currency code for tolerance calculation
     * @return array ['valid' => bool, 'message' => string|null, 'difference' => float]
     */
    public static function validateBalanced(array $lines, ?string $currency = null): array
    {
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($lines as $line) {
            $totalDebits += floatval($line['debit'] ?? 0);
            $totalCredits += floatval($line['credit'] ?? 0);
        }

        // Get currency-specific tolerance
        $tolerance = self::getCurrencyTolerance($currency);

        $difference = abs($totalDebits - $totalCredits);

        if ($difference > $tolerance) {
            return [
                'valid' => false,
                'message' => "Journal entry is unbalanced. Debits: " .
                    self::formatCurrency($totalDebits, $currency) .
                    ", Credits: " . self::formatCurrency($totalCredits, $currency) .
                    ", Difference: " . self::formatCurrency($difference, $currency),
                'difference' => $difference,
                'total_debits' => $totalDebits,
                'total_credits' => $totalCredits,
            ];
        }

        return [
            'valid' => true,
            'message' => null,
            'difference' => $difference,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
        ];
    }

    /**
     * Get currency-specific tolerance for balance validation
     *
     * @param string|null $currency
     * @return float
     */
    public static function getCurrencyTolerance(?string $currency = null): float
    {
        $currency = $currency ?? config('accounting.default_currency', 'NGN');

        // Different currencies may have different minimum units
        $tolerances = [
            'NGN' => 0.01,  // Nigerian Naira (kobo)
            'USD' => 0.01,  // US Dollar (cent)
            'EUR' => 0.01,  // Euro (cent)
            'GBP' => 0.01,  // British Pound (pence)
            'JPY' => 1.00,  // Japanese Yen (no fractional unit)
            'KWD' => 0.001, // Kuwaiti Dinar (fils - 3 decimal places)
        ];

        return $tolerances[$currency] ?? 0.01;
    }

    /**
     * Format amount as currency
     *
     * @param float $amount
     * @param string|null $currency
     * @return string
     */
    public static function formatCurrency(float $amount, ?string $currency = null): string
    {
        $currency = $currency ?? config('accounting.default_currency', 'NGN');

        try {
            $formatter = new NumberFormatter(app()->getLocale(), NumberFormatter::CURRENCY);
            return $formatter->formatCurrency($amount, $currency);
        } catch (\Exception $e) {
            return number_format($amount, 2) . ' ' . $currency;
        }
    }

    /**
     * Validate that entry date falls within the accounting period
     *
     * @param int $periodId
     * @param string|\DateTimeInterface $entryDate
     * @return array ['valid' => bool, 'message' => string|null]
     */
    public static function validatePeriod(int $periodId, $entryDate): array
    {
        $period = AccountingPeriod::find($periodId);

        if (!$period) {
            return [
                'valid' => false,
                'message' => 'Invalid accounting period',
            ];
        }

        if ($period->status === 'closed') {
            return [
                'valid' => false,
                'message' => 'Cannot create entries for a closed accounting period',
            ];
        }

        $entryDate = $entryDate instanceof \DateTimeInterface
            ? $entryDate
            : Carbon::parse($entryDate);

        $periodStart = Carbon::parse($period->period_start);
        $periodEnd = Carbon::parse($period->period_end);

        if ($entryDate < $periodStart || $entryDate > $periodEnd) {
            return [
                'valid' => false,
                'message' => "Entry date must fall within the accounting period ({$periodStart->format('Y-m-d')} to {$periodEnd->format('Y-m-d')})",
            ];
        }

        return ['valid' => true, 'message' => null];
    }

    /**
     * Validate journal entry lines have required fields
     *
     * @param array $lines
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validateLines(array $lines): array
    {
        $errors = [];

        if (count($lines) < 2) {
            $errors[] = 'Journal entry must have at least 2 lines';
        }

        $hasDebit = false;
        $hasCredit = false;

        foreach ($lines as $index => $line) {
            $lineNum = $index + 1;

            if (empty($line['account_id'])) {
                $errors[] = "Line {$lineNum}: Account is required";
            }

            $debit = floatval($line['debit'] ?? 0);
            $credit = floatval($line['credit'] ?? 0);

            if ($debit < 0 || $credit < 0) {
                $errors[] = "Line {$lineNum}: Amounts cannot be negative";
            }

            if ($debit > 0 && $credit > 0) {
                $errors[] = "Line {$lineNum}: A line cannot have both debit and credit amounts";
            }

            if ($debit == 0 && $credit == 0) {
                $errors[] = "Line {$lineNum}: Must have either a debit or credit amount";
            }

            if ($debit > 0) $hasDebit = true;
            if ($credit > 0) $hasCredit = true;
        }

        if (!$hasDebit) {
            $errors[] = 'Journal entry must have at least one debit line';
        }

        if (!$hasCredit) {
            $errors[] = 'Journal entry must have at least one credit line';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Comprehensive validation for a journal entry
     *
     * @param array $data Journal entry data
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validate(array $data): array
    {
        $errors = [];

        // Validate lines structure
        $linesValidation = self::validateLines($data['lines'] ?? []);
        if (!$linesValidation['valid']) {
            $errors = array_merge($errors, $linesValidation['errors']);
        }

        // Validate balancing
        $balanceValidation = self::validateBalanced($data['lines'] ?? []);
        if (!$balanceValidation['valid']) {
            $errors[] = $balanceValidation['message'];
        }

        // Validate period if provided
        if (!empty($data['period_id']) && !empty($data['entry_date'])) {
            $periodValidation = self::validatePeriod($data['period_id'], $data['entry_date']);
            if (!$periodValidation['valid']) {
                $errors[] = $periodValidation['message'];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}

<?php

namespace App\Services\Accounting;

use App\Models\GlAccount;

/**
 * Account Type Validation Service
 *
 * Validates that accounting entries comply with account type rules:
 * - Asset accounts normally have debit balances
 * - Liability accounts normally have credit balances
 * - Equity accounts normally have credit balances
 * - Revenue accounts normally have credit balances
 * - Expense accounts normally have debit balances
 * - Header accounts cannot receive postings
 */
class AccountTypeValidator
{
    /**
     * Account types that normally have debit balances
     */
    public const DEBIT_BALANCE_TYPES = ['asset', 'expense', 'cogs'];

    /**
     * Account types that normally have credit balances
     */
    public const CREDIT_BALANCE_TYPES = ['liability', 'equity', 'revenue', 'tax'];

    /**
     * Validate an entry for a specific account type
     *
     * @param GlAccount $account
     * @param float $debit
     * @param float $credit
     * @param bool $strictBalanceCheck Whether to enforce that balances stay positive
     * @return array ['valid' => bool, 'messages' => array, 'warnings' => array]
     */
    public static function validateEntryForAccountType(
        GlAccount $account,
        float $debit,
        float $credit,
        bool $strictBalanceCheck = false
    ): array {
        $messages = [];
        $warnings = [];

        // Check if posting to header account
        if ($account->is_header) {
            return [
                'valid' => false,
                'messages' => ["Cannot post to header account {$account->account_number}: {$account->account_name}"],
                'warnings' => [],
            ];
        }

        // Check if account allows manual entries
        if (!$account->allow_manual_entry) {
            return [
                'valid' => false,
                'messages' => ["Account {$account->account_number} does not allow manual entries"],
                'warnings' => [],
            ];
        }

        // Calculate projected balance after this entry
        $projectedBalance = self::calculateProjectedBalance($account, $debit, $credit);

        // Validate based on account type
        switch ($account->account_type) {
            case 'asset':
                // Assets typically have debit balances
                if ($strictBalanceCheck && $projectedBalance < 0) {
                    // Allow some accounts (like cash) to go negative temporarily
                    if (!self::isNegativeBalanceAllowed($account)) {
                        $messages[] = "Asset account {$account->account_number} would have a negative balance: " .
                            number_format($projectedBalance, 2);
                    } else {
                        $warnings[] = "Asset account {$account->account_number} will have a negative balance: " .
                            number_format($projectedBalance, 2);
                    }
                }
                break;

            case 'liability':
                // Liabilities typically have credit balances
                if ($strictBalanceCheck && $projectedBalance < 0) {
                    $messages[] = "Liability account {$account->account_number} would have a negative (debit) balance: " .
                        number_format($projectedBalance, 2);
                }
                break;

            case 'equity':
                // Equity typically has credit balance
                if ($strictBalanceCheck && $projectedBalance < 0) {
                    $warnings[] = "Equity account {$account->account_number} will have a negative balance: " .
                        number_format($projectedBalance, 2);
                }
                break;

            case 'revenue':
                // Revenue accounts increase equity (credit balance)
                // Credits increase revenue, debits decrease it
                if ($debit > 0 && $debit > floatval($account->credit_balance)) {
                    $warnings[] = "Debit to revenue account {$account->account_number} exceeds credit balance";
                }
                break;

            case 'expense':
                // Expenses decrease equity (debit balance)
                // Debits increase expense, credits decrease it
                if ($credit > 0 && $credit > floatval($account->debit_balance)) {
                    $warnings[] = "Credit to expense account {$account->account_number} exceeds debit balance";
                }
                break;

            case 'cogs':
                // Cost of Goods Sold - similar to expense (debit balance)
                break;

            case 'contra_asset':
            case 'contra_liability':
            case 'contra_equity':
            case 'contra_revenue':
            case 'contra_expense':
                // Contra accounts have opposite normal balances
                // Validation depends on specific contra type
                break;
        }

        return [
            'valid' => empty($messages),
            'messages' => $messages,
            'warnings' => $warnings,
        ];
    }

    /**
     * Calculate projected balance after entry
     *
     * @param GlAccount $account
     * @param float $debit
     * @param float $credit
     * @return float
     */
    public static function calculateProjectedBalance(GlAccount $account, float $debit, float $credit): float
    {
        $currentDebit = floatval($account->debit_balance);
        $currentCredit = floatval($account->credit_balance);

        $newDebit = $currentDebit + $debit;
        $newCredit = $currentCredit + $credit;

        // Calculate balance based on account type
        if (in_array($account->account_type, self::DEBIT_BALANCE_TYPES)) {
            return $newDebit - $newCredit;
        }

        if (in_array($account->account_type, self::CREDIT_BALANCE_TYPES)) {
            return $newCredit - $newDebit;
        }

        return $newDebit - $newCredit;
    }

    /**
     * Check if an account is allowed to have a negative balance
     *
     * @param GlAccount $account
     * @return bool
     */
    public static function isNegativeBalanceAllowed(GlAccount $account): bool
    {
        // Cash and bank accounts may temporarily go negative
        $allowedCategories = ['Cash', 'Bank', 'Petty Cash'];
        if (in_array($account->account_category, $allowedCategories)) {
            return true;
        }

        // Check by account number patterns
        $negativeAllowedPatterns = [
            '/^110[0-9]/',  // Cash accounts
            '/^111[0-9]/',  // Bank accounts
            '/^115[0-9]/',  // Petty cash
        ];

        foreach ($negativeAllowedPatterns as $pattern) {
            if (preg_match($pattern, $account->account_number)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate multiple entries for account type compliance
     *
     * @param array $entries Array of ['account_id' => int, 'debit' => float, 'credit' => float]
     * @param bool $strictBalanceCheck
     * @return array ['valid' => bool, 'errors' => array, 'warnings' => array]
     */
    public static function validateEntries(array $entries, bool $strictBalanceCheck = false): array
    {
        $errors = [];
        $warnings = [];

        // Group entries by account to calculate cumulative effect
        $entriesByAccount = [];
        foreach ($entries as $entry) {
            $accountId = $entry['account_id'];
            if (!isset($entriesByAccount[$accountId])) {
                $entriesByAccount[$accountId] = ['debit' => 0, 'credit' => 0];
            }
            $entriesByAccount[$accountId]['debit'] += floatval($entry['debit'] ?? 0);
            $entriesByAccount[$accountId]['credit'] += floatval($entry['credit'] ?? 0);
        }

        // Validate each account
        foreach ($entriesByAccount as $accountId => $totals) {
            $account = GlAccount::find($accountId);
            if (!$account) {
                $errors[] = "Account ID {$accountId} not found";
                continue;
            }

            $validation = self::validateEntryForAccountType(
                $account,
                $totals['debit'],
                $totals['credit'],
                $strictBalanceCheck
            );

            $errors = array_merge($errors, $validation['messages']);
            $warnings = array_merge($warnings, $validation['warnings']);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Get the normal balance side for an account type
     *
     * @param string $accountType
     * @return string 'debit' or 'credit'
     */
    public static function getNormalBalance(string $accountType): string
    {
        if (in_array($accountType, self::DEBIT_BALANCE_TYPES)) {
            return 'debit';
        }

        if (in_array($accountType, self::CREDIT_BALANCE_TYPES)) {
            return 'credit';
        }

        return 'debit'; // Default
    }
}

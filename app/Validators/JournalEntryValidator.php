<?php

namespace App\Validators;

use App\Models\GlAccount;
use App\Models\GlEntry;
use App\Models\AccountingPeriod;
use Exception;

class JournalEntryValidator
{
    private array $errors = [];

    /**
     * Validate GL entry before posting
     */
    public function validate(GlEntry $entry): bool
    {
        $this->errors = [];

        $this->validateAccountBalance($entry);
        $this->validateAccountExists($entry);
        $this->validatePeriodIsOpen($entry);
        $this->validateAmounts($entry);
        $this->validateDescription($entry);

        return empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get error message
     */
    public function getErrorMessage(): string
    {
        return implode('; ', $this->errors);
    }

    /**
     * Validate debit equals credit
     */
    private function validateAccountBalance(GlEntry $entry): void
    {
        // For individual entries, this would typically be checked at the journal batch level
        // But we can add rules here if needed
    }

    /**
     * Validate GL account exists and is active
     */
    private function validateAccountExists(GlEntry $entry): void
    {
        $account = GlAccount::find($entry->gl_account_id);

        if (!$account) {
            $this->errors[] = "GL Account #{$entry->gl_account_id} does not exist";
            return;
        }

        if (!$account->is_active) {
            $this->errors[] = "GL Account {$account->account_number} ({$account->account_name}) is inactive";
        }

        if ($account->is_header) {
            $this->errors[] = "GL Account {$account->account_number} is a header account and cannot have entries";
        }
    }

    /**
     * Validate accounting period is open
     */
    private function validatePeriodIsOpen(GlEntry $entry): void
    {
        $period = AccountingPeriod::find($entry->accounting_period_id);

        if (!$period) {
            $this->errors[] = "Accounting period #{$entry->accounting_period_id} does not exist";
            return;
        }

        if ($period->status !== 'open') {
            $this->errors[] = "Accounting period {$period->getDisplayName()} is not open (Status: {$period->status})";
        }

        if ($entry->entry_date < $period->period_start) {
            $this->errors[] = "Entry date cannot be before period start date ({$period->period_start->toDateString()})";
        }

        if ($entry->entry_date > $period->period_end) {
            $this->errors[] = "Entry date cannot be after period end date ({$period->period_end->toDateString()})";
        }
    }

    /**
     * Validate amounts are valid
     */
    private function validateAmounts(GlEntry $entry): void
    {
        $debit = floatval($entry->debit);
        $credit = floatval($entry->credit);

        if ($debit < 0) {
            $this->errors[] = "Debit amount cannot be negative";
        }

        if ($credit < 0) {
            $this->errors[] = "Credit amount cannot be negative";
        }

        if ($debit == 0 && $credit == 0) {
            $this->errors[] = "Entry must have either a debit or credit amount";
        }

        // Allow small tolerance for floating-point precision
        if (abs(($debit - $credit)) > 0.01) {
            // This check is for when both debit and credit are specified
            // In normal usage, one should be 0
        }
    }

    /**
     * Validate description is provided
     */
    private function validateDescription(GlEntry $entry): void
    {
        if (empty(trim($entry->description))) {
            $this->errors[] = "Description is required";
        }
    }

    /**
     * Validate journal batch (multiple entries)
     */
    public function validateBatch(array $entries): bool
    {
        $this->errors = [];

        if (empty($entries)) {
            $this->errors[] = "Journal batch must contain at least one entry";
            return false;
        }

        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($entries as $entry) {
            if (!$this->validate($entry)) {
                return false;
            }

            $totalDebits += floatval($entry->debit);
            $totalCredits += floatval($entry->credit);
        }

        // Check if batch balances
        if (abs($totalDebits - $totalCredits) > 0.01) {
            $this->errors[] = "Journal batch does not balance. Total Debits: {$totalDebits}, Total Credits: {$totalCredits}";
            return false;
        }

        return true;
    }

    /**
     * Prevent posting to closed period
     */
    public function canPostToPeriod(AccountingPeriod $period): bool
    {
        if ($period->status === 'locked') {
            $this->errors[] = "Cannot post to locked period {$period->getDisplayName()}";
            return false;
        }

        if ($period->status === 'closed') {
            $this->errors[] = "Cannot post to closed period {$period->getDisplayName()}";
            return false;
        }

        return true;
    }

    /**
     * Check if account can accept manual entries
     */
    public function canPostToAccount(GlAccount $account): bool
    {
        if (!$account->allow_manual_entry) {
            $this->errors[] = "Manual entries are not allowed for account {$account->account_number}";
            return false;
        }

        return true;
    }
}

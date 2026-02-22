<?php

namespace App\Services\Accounting;

use App\Models\GlAccount;
use App\Models\GlEntry;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Accounting Audit Service
 *
 * Provides comprehensive audit trail logging for accounting operations.
 * All critical accounting activities are logged for compliance and review.
 */
class AccountingAuditService
{
    /**
     * Log journal entry creation
     *
     * @param GlEntry $entry
     * @param User $user
     * @return void
     */
    public function logEntryCreation(GlEntry $entry, User $user): void
    {
        $properties = [
            'action' => 'create',
            'entry_id' => $entry->id,
            'gl_account' => $entry->glAccount?->account_number . ': ' . $entry->glAccount?->account_name,
            'account_type' => $entry->glAccount?->account_type,
            'debit' => $entry->debit,
            'credit' => $entry->credit,
            'reference' => $entry->reference_number,
            'period' => $entry->period?->name,
            'status' => $entry->status,
            'description' => $entry->description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        $this->logActivity($entry, $user, 'gl_entry_created', $properties);
    }

    /**
     * Log journal entry posting
     *
     * @param GlEntry $entry
     * @param User $user
     * @param string $previousStatus
     * @return void
     */
    public function logEntryPosting(GlEntry $entry, User $user, string $previousStatus = 'draft'): void
    {
        $properties = [
            'action' => 'post',
            'entry_id' => $entry->id,
            'gl_account' => $entry->glAccount?->account_number . ': ' . $entry->glAccount?->account_name,
            'previous_status' => $previousStatus,
            'new_status' => $entry->status,
            'posted_by' => $user->name,
            'posting_time' => now()->toIso8601String(),
            'debit' => $entry->debit,
            'credit' => $entry->credit,
            'account_balance_change' => $this->calculateAccountBalanceChange($entry),
            'ip_address' => request()->ip(),
        ];

        $this->logActivity($entry, $user, 'gl_entry_posted', $properties);
    }

    /**
     * Log journal entry reversal
     *
     * @param GlEntry $entry
     * @param User $user
     * @param string|null $reason
     * @return void
     */
    public function logEntryReversal(GlEntry $entry, User $user, ?string $reason = null): void
    {
        $properties = [
            'action' => 'reverse',
            'entry_id' => $entry->id,
            'gl_account' => $entry->glAccount?->account_number . ': ' . $entry->glAccount?->account_name,
            'reversed_by' => $user->name,
            'reversal_time' => now()->toIso8601String(),
            'reason' => $reason,
            'original_debit' => $entry->debit,
            'original_credit' => $entry->credit,
            'ip_address' => request()->ip(),
        ];

        $this->logActivity($entry, $user, 'gl_entry_reversed', $properties);
    }

    /**
     * Log journal entry update
     *
     * @param GlEntry $entry
     * @param User $user
     * @param array $changes
     * @return void
     */
    public function logEntryUpdate(GlEntry $entry, User $user, array $changes): void
    {
        $properties = [
            'action' => 'update',
            'entry_id' => $entry->id,
            'gl_account' => $entry->glAccount?->account_number,
            'changes' => $changes,
            'updated_by' => $user->name,
            'update_time' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
        ];

        $this->logActivity($entry, $user, 'gl_entry_updated', $properties);
    }

    /**
     * Log journal entry deletion
     *
     * @param GlEntry $entry
     * @param User $user
     * @param string|null $reason
     * @return void
     */
    public function logEntryDeletion(GlEntry $entry, User $user, ?string $reason = null): void
    {
        $properties = [
            'action' => 'delete',
            'entry_id' => $entry->id,
            'gl_account' => $entry->glAccount?->account_number . ': ' . $entry->glAccount?->account_name,
            'deleted_by' => $user->name,
            'deletion_time' => now()->toIso8601String(),
            'reason' => $reason,
            'entry_data' => [
                'debit' => $entry->debit,
                'credit' => $entry->credit,
                'description' => $entry->description,
                'reference' => $entry->reference_number,
            ],
            'ip_address' => request()->ip(),
        ];

        $this->logActivity($entry, $user, 'gl_entry_deleted', $properties);
    }

    /**
     * Log bulk operation on entries
     *
     * @param string $operation
     * @param array $entryIds
     * @param User $user
     * @param array $additionalData
     * @return void
     */
    public function logBulkOperation(string $operation, array $entryIds, User $user, array $additionalData = []): void
    {
        $properties = array_merge([
            'action' => 'bulk_operation',
            'operation' => $operation,
            'entry_ids' => $entryIds,
            'count' => count($entryIds),
            'performed_by' => $user->name,
            'performed_at' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
        ], $additionalData);

        $this->logActivity(null, $user, 'gl_bulk_operation', $properties);
    }

    /**
     * Log account balance update
     *
     * @param GlAccount $account
     * @param float $previousDebitBalance
     * @param float $previousCreditBalance
     * @param User|null $user
     * @param GlEntry|null $triggeringEntry
     * @return void
     */
    public function logAccountBalanceUpdate(
        GlAccount $account,
        float $previousDebitBalance,
        float $previousCreditBalance,
        ?User $user = null,
        ?GlEntry $triggeringEntry = null
    ): void {
        $properties = [
            'action' => 'balance_update',
            'account_id' => $account->id,
            'account_number' => $account->account_number,
            'account_name' => $account->account_name,
            'previous_debit_balance' => $previousDebitBalance,
            'previous_credit_balance' => $previousCreditBalance,
            'new_debit_balance' => $account->debit_balance,
            'new_credit_balance' => $account->credit_balance,
            'debit_change' => $account->debit_balance - $previousDebitBalance,
            'credit_change' => $account->credit_balance - $previousCreditBalance,
            'triggering_entry_id' => $triggeringEntry?->id,
            'updated_at' => now()->toIso8601String(),
        ];

        $this->logActivity($account, $user, 'gl_account_balance_updated', $properties);
    }

    /**
     * Log period closure
     *
     * @param \App\Models\AccountingPeriod $period
     * @param User $user
     * @return void
     */
    public function logPeriodClosure($period, User $user): void
    {
        $properties = [
            'action' => 'period_closure',
            'period_id' => $period->id,
            'period_name' => $period->name,
            'period_start' => $period->period_start,
            'period_end' => $period->period_end,
            'closed_by' => $user->name,
            'closed_at' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
        ];

        $this->logActivity($period, $user, 'accounting_period_closed', $properties);
    }

    /**
     * Log approval action
     *
     * @param GlEntry $entry
     * @param User $approver
     * @param string $action 'approved' or 'rejected'
     * @param string|null $comments
     * @return void
     */
    public function logApprovalAction(GlEntry $entry, User $approver, string $action, ?string $comments = null): void
    {
        $properties = [
            'action' => $action,
            'entry_id' => $entry->id,
            'gl_account' => $entry->glAccount?->account_number,
            'approver' => $approver->name,
            'approver_role' => $approver->roles->pluck('name')->implode(', '),
            'requester_id' => $entry->entered_by_id,
            'comments' => $comments,
            'decision_time' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
        ];

        $this->logActivity($entry, $approver, "gl_entry_{$action}", $properties);
    }

    /**
     * Calculate account balance change from an entry
     *
     * @param GlEntry $entry
     * @return float
     */
    private function calculateAccountBalanceChange(GlEntry $entry): float
    {
        $account = $entry->glAccount;

        if (!$account) {
            return 0;
        }

        if (in_array($account->account_type, ['asset', 'expense', 'cogs'])) {
            return floatval($entry->debit) - floatval($entry->credit);
        } else {
            return floatval($entry->credit) - floatval($entry->debit);
        }
    }

    /**
     * Log activity using Laravel's activity log or custom logging
     *
     * @param mixed $subject
     * @param User|null $user
     * @param string $description
     * @param array $properties
     * @return void
     */
    private function logActivity($subject, ?User $user, string $description, array $properties): void
    {
        // Use spatie/laravel-activitylog if available
        if (function_exists('activity')) {
            $activity = activity('accounting')
                ->withProperties($properties);

            if ($subject) {
                $activity->performedOn($subject);
            }

            if ($user) {
                $activity->causedBy($user);
            }

            $activity->log($description);
        }

        // Also log to standard log for backup
        Log::channel('accounting')->info($description, array_merge($properties, [
            'user_id' => $user?->id,
            'user_name' => $user?->name,
        ]));
    }

    /**
     * Get audit trail for an entry
     *
     * @param GlEntry $entry
     * @return \Illuminate\Support\Collection
     */
    public function getEntryAuditTrail(GlEntry $entry)
    {
        if (function_exists('activity')) {
            return \Spatie\Activitylog\Models\Activity::where('subject_type', GlEntry::class)
                ->where('subject_id', $entry->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return collect();
    }

    /**
     * Get audit trail for an account
     *
     * @param GlAccount $account
     * @return \Illuminate\Support\Collection
     */
    public function getAccountAuditTrail(GlAccount $account)
    {
        if (function_exists('activity')) {
            return \Spatie\Activitylog\Models\Activity::where('subject_type', GlAccount::class)
                ->where('subject_id', $account->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return collect();
    }
}

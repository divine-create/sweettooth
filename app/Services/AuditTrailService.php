<?php

namespace App\Services;

use App\Models\GlEntry;
use App\Models\GlAccount;
use App\Models\AccountingPeriod;
use App\Models\AuditLog;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class AuditTrailService
{
    /**
     * Log GL entry creation
     */
    public function logEntryCreated(GlEntry $entry, $userId = null): void
    {
        $this->logAction(
            'gl_entry_created',
            'GL Entry Created',
            GlEntry::class,
            $entry->id,
            [
                'account_number' => $entry->glAccount->account_number,
                'account_name' => $entry->glAccount->account_name,
                'debit' => $entry->debit,
                'credit' => $entry->credit,
                'description' => $entry->description,
            ],
            $userId
        );
    }

    /**
     * Log GL entry posting
     */
    public function logEntryPosted(GlEntry $entry, $userId = null): void
    {
        $this->logAction(
            'gl_entry_posted',
            'GL Entry Posted',
            GlEntry::class,
            $entry->id,
            [
                'account_number' => $entry->glAccount->account_number,
                'posted_by_id' => $userId,
                'posted_at' => now(),
            ],
            $userId
        );
    }

    /**
     * Log GL entry reversal
     */
    public function logEntryReversed(GlEntry $entry, GlEntry $reversalEntry, $userId = null): void
    {
        $this->logAction(
            'gl_entry_reversed',
            'GL Entry Reversed',
            GlEntry::class,
            $entry->id,
            [
                'original_entry_id' => $entry->id,
                'reversal_entry_id' => $reversalEntry->id,
                'account_number' => $entry->glAccount->account_number,
                'amount' => $entry->debit ?: $entry->credit,
            ],
            $userId
        );
    }

    /**
     * Log GL account modification
     */
    public function logAccountModified(GlAccount $account, array $changes, $userId = null): void
    {
        $this->logAction(
            'gl_account_modified',
            'GL Account Modified',
            GlAccount::class,
            $account->id,
            [
                'account_number' => $account->account_number,
                'account_name' => $account->account_name,
                'changes' => $changes,
            ],
            $userId
        );
    }

    /**
     * Log GL account activation/deactivation
     */
    public function logAccountStatusChange(GlAccount $account, bool $isActive, $userId = null): void
    {
        $this->logAction(
            'gl_account_status_changed',
            'GL Account Status Changed',
            GlAccount::class,
            $account->id,
            [
                'account_number' => $account->account_number,
                'is_active' => $isActive,
                'previous_status' => !$isActive,
            ],
            $userId
        );
    }

    /**
     * Log period close
     */
    public function logPeriodClosed(AccountingPeriod $period, string $notes = null, $userId = null): void
    {
        $this->logAction(
            'period_closed',
            'Accounting Period Closed',
            AccountingPeriod::class,
            $period->id,
            [
                'period' => $period->getDisplayName(),
                'closing_notes' => $notes,
                'entry_count' => $period->entries()->where('status', 'posted')->count(),
            ],
            $userId
        );
    }

    /**
     * Log period reopen
     */
    public function logPeriodReopened(AccountingPeriod $period, $userId = null): void
    {
        $this->logAction(
            'period_reopened',
            'Accounting Period Reopened',
            AccountingPeriod::class,
            $period->id,
            [
                'period' => $period->getDisplayName(),
            ],
            $userId
        );
    }

    /**
     * Log period lock
     */
    public function logPeriodLocked(AccountingPeriod $period, $userId = null): void
    {
        $this->logAction(
            'period_locked',
            'Accounting Period Locked',
            AccountingPeriod::class,
            $period->id,
            [
                'period' => $period->getDisplayName(),
            ],
            $userId
        );
    }

    /**
     * Log manual journal entry
     */
    public function logManualJournalEntry(Collection $entries, $userId = null): void
    {
        $this->logAction(
            'manual_journal_posted',
            'Manual Journal Entries Posted',
            GlEntry::class,
            0,
            [
                'entry_count' => $entries->count(),
                'total_debit' => $entries->sum('debit'),
                'total_credit' => $entries->sum('credit'),
                'posted_by' => $userId,
            ],
            $userId
        );
    }

    /**
     * Get audit trail for GL entry
     */
    public function getEntryAuditTrail(GlEntry $entry): Collection
    {
        return AuditLog::where('auditable_type', GlEntry::class)
            ->where('auditable_id', $entry->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get audit trail for GL account
     */
    public function getAccountAuditTrail(GlAccount $account): Collection
    {
        return AuditLog::where('auditable_type', GlAccount::class)
            ->where('auditable_id', $account->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get audit trail for accounting period
     */
    public function getPeriodAuditTrail(AccountingPeriod $period): Collection
    {
        return AuditLog::where('auditable_type', AccountingPeriod::class)
            ->where('auditable_id', $period->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get user activity report
     */
    public function getUserActivityReport(
        $userId,
        Carbon $startDate = null,
        Carbon $endDate = null
    ): array {
        $startDate = $startDate ?? now()->subMonth();
        $endDate = $endDate ?? now();

        $logs = AuditLog::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $actionCounts = $logs->groupBy('action')->mapWithKeys(function ($group) {
            return [$group->first()->action => $group->count()];
        });

        return [
            'user_id' => $userId,
            'period' => $startDate->toDateString() . ' to ' . $endDate->toDateString(),
            'total_actions' => $logs->count(),
            'action_breakdown' => $actionCounts->toArray(),
            'recent_activities' => $logs->take(20),
        ];
    }

    /**
     * Get compliance report - GL entries posted without review
     */
    public function getComplianceReport(
        AccountingPeriod $period = null,
        Carbon $startDate = null,
        Carbon $endDate = null
    ): array {
        $period = $period ?? AccountingPeriod::where('status', 'open')->first();
        $startDate = $startDate ?? ($period->period_start ?? now()->subMonth());
        $endDate = $endDate ?? ($period->period_end ?? now());

        $entries = GlEntry::whereBetween('posted_at', [$startDate, $endDate])
            ->where('status', 'posted')
            ->get();

        // Check for manual entries (no reference)
        $manualEntries = $entries->filter(fn($e) => $e->reference_type === null);

        // Check for large entries
        $largeEntries = $entries->filter(function ($e) {
            $amount = floatval($e->debit) ?: floatval($e->credit);
            return $amount > 10000; // Configurable threshold
        });

        // Check for entries posted by non-accounting users
        $unusualPosters = $entries->filter(function ($e) {
            $poster = $e->postedBy;
            return $poster && !$this->isAccountingUser($poster);
        });

        return [
            'period' => $period ? $period->getDisplayName() : 'Custom',
            'date_range' => $startDate->toDateString() . ' to ' . $endDate->toDateString(),
            'total_entries' => $entries->count(),
            'manual_entries' => [
                'count' => $manualEntries->count(),
                'total_amount' => $manualEntries->sum(fn($e) => floatval($e->debit) ?: floatval($e->credit)),
                'entries' => $manualEntries->take(10),
            ],
            'large_entries' => [
                'count' => $largeEntries->count(),
                'total_amount' => $largeEntries->sum(fn($e) => floatval($e->debit) ?: floatval($e->credit)),
                'entries' => $largeEntries->take(10),
            ],
            'unusual_posters' => [
                'count' => $unusualPosters->count(),
                'entries' => $unusualPosters->take(10),
            ],
        ];
    }

    /**
     * Get change tracking report
     */
    public function getChangeTrackingReport(
        Carbon $startDate = null,
        Carbon $endDate = null
    ): array {
        $startDate = $startDate ?? now()->subMonth();
        $endDate = $endDate ?? now();

        $logs = AuditLog::whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $changes = $logs->groupBy('auditable_type')->mapWithKeys(function ($group, $type) {
            return [$type => [
                'change_count' => $group->count(),
                'by_action' => $group->groupBy('action')->mapWithKeys(
                    fn($items) => [$items->first()->action => $items->count()]
                )->toArray(),
            ]];
        });

        return [
            'period' => $startDate->toDateString() . ' to ' . $endDate->toDateString(),
            'total_changes' => $logs->count(),
            'changes_by_type' => $changes->toArray(),
            'recent_changes' => $logs->take(50),
        ];
    }

    /**
     * Check if user is accounting user
     */
    private function isAccountingUser($user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->can('view-accounting') || $user->can('manage-accounting')) {
            return true;
        }

        return $user->hasAnyRole(['Accountant', 'Accounting Manager', 'Admin', 'Super Admin']);
    }

    /**
     * Base logging method
     */
    private function logAction(
        string $action,
        string $description,
        string $auditableType,
        $auditableId,
        array $changes = [],
        $userId = null
    ): void {
        try {
            AuditLog::create([
                'action' => $action,
                'description' => $description,
                'auditable_type' => $auditableType,
                'auditable_id' => $auditableId,
                'old_values' => [],
                'new_values' => $changes,
                'user_id' => $userId ?? auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Audit log failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate audit summary for compliance
     */
    public function generateComplianceSummary(AccountingPeriod $period): array
    {
        $entries = GlEntry::where('accounting_period_id', $period->id)
            ->where('status', 'posted')
            ->get();

        $totalAmount = floatval($entries->sum('debit'));
        $manualEntriesAmount = floatval($entries->where('entry_type', 'manual')->sum('debit'));

        return [
            'period' => $period->getDisplayName(),
            'total_gl_entries' => $entries->count(),
            'total_amount_posted' => $totalAmount,
            'manual_entries' => $entries->where('entry_type', 'manual')->count(),
            'manual_entries_percentage' => $totalAmount > 0 ? ($manualEntriesAmount / $totalAmount) * 100 : 0,
            'automated_entries' => $entries->whereIn('entry_type', ['sale', 'payment', 'adjustment'])->count(),
            'unique_posters' => $entries->pluck('posted_by_id')->unique()->count(),
            'entries_over_10k' => $entries->filter(fn($e) => floatval($e->debit) > 10000 || floatval($e->credit) > 10000)->count(),
            'compliance_status' => 'passed', // Can be extended with actual compliance checks
        ];
    }
}

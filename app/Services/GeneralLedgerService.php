<?php

namespace App\Services;

use App\Models\GlEntry;
use App\Models\GlAccount;
use App\Models\AccountingPeriod;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class GeneralLedgerService
{
    /**
     * Get GL entries with filters
     */
    public function getEntries(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?int $glAccountId = null,
        ?int $periodId = null,
        ?string $status = null,
        ?string $entryType = null,
    ): Collection {
        $query = GlEntry::query();

        // Filter by date range
        if ($startDate) {
            $query->where('entry_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('entry_date', '<=', $endDate);
        }

        // Filter by GL account
        if ($glAccountId) {
            $query->where('gl_account_id', $glAccountId);
        }

        // Filter by period
        if ($periodId) {
            $query->where('accounting_period_id', $periodId);
        }

        // Filter by status
        if ($status) {
            $query->where('status', $status);
        }

        // Filter by entry type
        if ($entryType) {
            $query->where('entry_type', $entryType);
        }

        return $query
            ->with(['glAccount', 'period'])
            ->orderBy('entry_date')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get GL entries by account
     */
    public function getEntriesByAccount(
        int $glAccountId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
    ): Collection {
        return $this->getEntries(
            startDate: $startDate,
            endDate: $endDate,
            glAccountId: $glAccountId,
            status: 'posted'
        );
    }

    /**
     * Get account balance for a period
     */
    public function getAccountBalance(
        int $glAccountId,
        ?int $periodId = null,
    ): float {
        $query = GlEntry::where('gl_account_id', $glAccountId)
            ->where('status', 'posted');

        if ($periodId) {
            $query->where('accounting_period_id', $periodId);
        }

        $debits = (float) $query->sum('debit');
        $credits = (float) $query->sum('credit');

        $account = GlAccount::find($glAccountId);
        if (!$account) {
            return 0;
        }

        // Determine balance based on normal balance
        if ($account->normal_balance === 'debit') {
            return $debits - $credits;
        } else {
            return $credits - $debits;
        }
    }

    /**
     * Get all account balances for a period
     */
    public function getAccountBalances(?int $periodId = null): array
    {
        $accounts = GlAccount::where('is_active', true)
            ->with('entries')
            ->get();

        $balances = [];
        foreach ($accounts as $account) {
            $balance = $this->getAccountBalance($account->id, $periodId);
            if ($balance != 0 || $account->account_type !== 'header') {
                $balances[$account->id] = [
                    'account' => $account,
                    'balance' => $balance,
                ];
            }
        }

        return $balances;
    }

    /**
     * Get GL summary by entry type
     */
    public function getSummaryByType(?int $periodId = null): array
    {
        $query = GlEntry::where('status', 'posted');

        if ($periodId) {
            $query->where('accounting_period_id', $periodId);
        }

        return $query
            ->groupBy('entry_type')
            ->selectRaw('entry_type, count(*) as count, sum(debit) as total_debit, sum(credit) as total_credit')
            ->get()
            ->toArray();
    }

    /**
     * Get GL entries for a specific reference
     */
    public function getEntriesByReference(
        string $referenceType,
        int $referenceId,
    ): Collection {
        return GlEntry::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->with(['glAccount', 'period'])
            ->orderBy('entry_date')
            ->get();
    }

    /**
     * Get GL entries paginated
     */
    public function getEntriesPaginated(
        int $perPage = 50,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?int $glAccountId = null,
        ?int $periodId = null,
    ) {
        $query = GlEntry::query();

        if ($startDate) {
            $query->where('entry_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('entry_date', '<=', $endDate);
        }
        if ($glAccountId) {
            $query->where('gl_account_id', $glAccountId);
        }
        if ($periodId) {
            $query->where('accounting_period_id', $periodId);
        }

        return $query
            ->with(['glAccount', 'period', 'enteredBy', 'postedBy'])
            ->where('status', 'posted')
            ->orderBy('entry_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get GL account details with all entries
     */
    public function getAccountDetails(
        int $glAccountId,
        ?int $periodId = null,
    ): array {
        $account = GlAccount::findOrFail($glAccountId);
        $entries = $this->getEntriesByAccount($glAccountId, null, null);
        $balance = $this->getAccountBalance($glAccountId, $periodId);

        return [
            'account' => $account,
            'entries' => $entries,
            'balance' => $balance,
            'entry_count' => $entries->count(),
            'total_debit' => $entries->sum('debit'),
            'total_credit' => $entries->sum('credit'),
        ];
    }

    /**
     * Export GL entries to array
     */
    public function exportEntries(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?int $glAccountId = null,
    ): array {
        $entries = $this->getEntries($startDate, $endDate, $glAccountId);

        return $entries->map(function ($entry) {
            $account = $entry->glAccount;
            $accountLabel = $account
                ? $account->account_number . ' - ' . $account->account_name
                : 'N/A';

            return [
                'Date' => $entry->entry_date->format('Y-m-d'),
                'Account' => $accountLabel,
                'Description' => $entry->description,
                'Reference' => $entry->reference_number,
                'Debit' => $entry->debit > 0 ? number_format($entry->debit, 2) : '',
                'Credit' => $entry->credit > 0 ? number_format($entry->credit, 2) : '',
                'Status' => $entry->status,
            ];
        })->toArray();
    }
}

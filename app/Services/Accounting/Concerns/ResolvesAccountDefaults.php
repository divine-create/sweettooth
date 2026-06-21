<?php

namespace App\Services\Accounting\Concerns;

use App\Models\BranchAccountingDefault;
use App\Models\GlAccount;
use RuntimeException;

/**
 * Resolves GL accounts for posting via the configurable branch_accounting_defaults
 * mapping (key -> account) instead of hard-coded account numbers.
 *
 * A branch-specific mapping takes precedence over a global (branch_id IS NULL) one.
 * Misconfiguration throws a clear error rather than posting to the wrong account.
 */
trait ResolvesAccountDefaults
{
    /** @var array<string,GlAccount> */
    protected array $accountDefaultCache = [];

    protected function resolveAccount(string $key, ?string $branchId = null): GlAccount
    {
        $cacheKey = ($branchId ?? 'global') . '|' . $key;
        if (isset($this->accountDefaultCache[$cacheKey])) {
            return $this->accountDefaultCache[$cacheKey];
        }

        $default = BranchAccountingDefault::query()
            ->where('key', $key)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
                if ($branchId !== null) {
                    $q->orWhereNull('branch_id');
                }
            })
            ->orderByRaw('branch_id IS NULL') // branch-specific (NULL=false) before global
            ->first();

        if (! $default || ! $default->gl_account_id) {
            throw new RuntimeException(
                "No GL account is configured for accounting key '{$key}'. "
                . 'Set it under Accounting Defaults before posting.'
            );
        }

        $account = GlAccount::find($default->gl_account_id);
        if (! $account) {
            throw new RuntimeException(
                "Accounting key '{$key}' points to a missing GL account (id {$default->gl_account_id})."
            );
        }

        return $this->accountDefaultCache[$cacheKey] = $account;
    }
}

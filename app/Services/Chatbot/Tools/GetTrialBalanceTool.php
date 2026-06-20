<?php

namespace App\Services\Chatbot\Tools;

use App\Services\Chatbot\Contracts\ChatTool;
use App\Services\TrialBalanceService;

/**
 * Read-only trial-balance summary for the CURRENT branch.
 */
class GetTrialBalanceTool implements ChatTool
{
    public function __construct(private TrialBalanceService $trialBalance) {}

    public function name(): string
    {
        return 'get_trial_balance';
    }

    public function description(): string
    {
        return 'Get the accounting trial balance for the current branch: total '
            . 'debits, total credits, whether the books are balanced, and the '
            . 'per-account debit/credit balances. Use for questions about the '
            . 'general ledger, account balances, or whether the books balance.';
    }

    public function permission(): ?string
    {
        return 'view-financial-reports';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
            'required' => [],
            'additionalProperties' => false,
        ];
    }

    public function handle(array $input): array
    {
        $branchId = current_branch_id();

        abort_unless($branchId !== null, 422, 'No active branch.');
        abort_unless(auth()->user()?->can($this->permission()), 403);

        $tb = $this->trialBalance->getTrialBalance(null, $branchId);

        return [
            'branch'        => current_branch()?->name,
            'total_debits'  => round((float) ($tb['total_debits'] ?? 0), 2),
            'total_credits' => round((float) ($tb['total_credits'] ?? 0), 2),
            'balanced'      => (bool) ($tb['balanced'] ?? false),
            'difference'    => round((float) ($tb['difference'] ?? 0), 2),
            'account_count' => count($tb['accounts'] ?? []),
            'accounts'      => array_map(fn ($a) => [
                'account_number' => $a['account_number'] ?? null,
                'account_name'   => $a['account_name'] ?? null,
                'debit'          => round((float) ($a['debit'] ?? 0), 2),
                'credit'         => round((float) ($a['credit'] ?? 0), 2),
            ], $tb['accounts'] ?? []),
        ];
    }
}

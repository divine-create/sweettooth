<?php

namespace App\Services\Chatbot\Tools;

use App\Services\BalanceSheetService;
use App\Services\Chatbot\Contracts\ChatTool;

/**
 * Read-only balance-sheet headline figures for the CURRENT branch.
 */
class GetBalanceSheetTool implements ChatTool
{
    public function __construct(private BalanceSheetService $balanceSheet) {}

    public function name(): string
    {
        return 'get_balance_sheet';
    }

    public function description(): string
    {
        return 'Get the balance sheet summary for the current branch: total assets, '
            . 'total liabilities, total equity (including retained earnings), and '
            . 'whether assets equal liabilities plus equity. Use for questions about '
            . 'assets, liabilities, equity, net worth, or the balance sheet.';
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
        abort_unless(is_super_admin() || auth()->user()?->can($this->permission()), 403);

        $bs = $this->balanceSheet->getBalanceSheet(null, $branchId);

        $money = fn ($k) => round((float) ($bs[$k] ?? 0), 2);

        return [
            'branch'                   => current_branch()?->name,
            'total_assets'             => $money('total_assets'),
            'total_liabilities'        => $money('total_liabilities'),
            'total_equity'             => $money('total_equity'),
            'retained_earnings'        => $money('retained_earnings'),
            'total_equity_with_re'     => $money('total_equity_with_re'),
            'total_liabilities_equity' => $money('total_liabilities_equity'),
            'is_balanced'              => (bool) ($bs['is_balanced'] ?? false),
            'difference'               => $money('difference'),
        ];
    }
}

<?php

namespace App\Services\Chatbot\Tools;

use App\Services\Chatbot\Contracts\ChatTool;
use App\Services\IncomeStatementService;

/**
 * Read-only income-statement (P&L) headline figures for the CURRENT branch.
 */
class GetIncomeStatementTool implements ChatTool
{
    public function __construct(private IncomeStatementService $incomeStatement) {}

    public function name(): string
    {
        return 'get_income_statement';
    }

    public function description(): string
    {
        return 'Get the profit & loss summary for the current branch: revenue, '
            . 'cost of goods sold, gross profit, operating expenses, EBIT, and net '
            . 'income with margins. Use for questions about profit, profitability, '
            . 'revenue vs expenses, or the income statement / P&L.';
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

        $is = $this->incomeStatement->getIncomeStatement(null, $branchId);

        $money = fn ($k) => round((float) ($is[$k] ?? 0), 2);

        return [
            'branch'              => current_branch()?->name,
            'total_revenue'       => $money('total_revenue'),
            'total_cogs'          => $money('total_cogs'),
            'gross_profit'        => $money('gross_profit'),
            'gross_profit_margin' => $money('gross_profit_margin'),
            'total_opex'          => $money('total_opex'),
            'total_expenses'      => $money('total_expenses'),
            'ebit'                => $money('ebit'),
            'net_income'          => $money('net_income'),
            'net_profit_margin'   => $money('net_profit_margin'),
        ];
    }
}

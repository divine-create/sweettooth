<?php

namespace App\Services\Chatbot\Tools;

use App\Services\CashFlowStatementService;
use App\Services\Chatbot\Contracts\ChatTool;
use Carbon\Carbon;

/**
 * Read-only cash-flow statement summary for the CURRENT branch.
 */
class GetCashFlowTool implements ChatTool
{
    public function __construct(private CashFlowStatementService $cashFlow) {}

    public function name(): string
    {
        return 'get_cash_flow';
    }

    public function description(): string
    {
        return 'Get the cash-flow statement for the current branch over a date '
            . 'range: cash from operating, investing and financing activities, the '
            . 'net change in cash, and opening/closing cash. Use for questions '
            . 'about cash flow, cash movement, or how cash changed. Dates YYYY-MM-DD.';
    }

    public function permission(): ?string
    {
        return 'view-financial-reports';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'from' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD). Defaults to start of this month.'],
                'to'   => ['type' => 'string', 'description' => 'End date (YYYY-MM-DD). Defaults to today.'],
            ],
            'required' => [],
            'additionalProperties' => false,
        ];
    }

    public function handle(array $input): array
    {
        $branchId = current_branch_id();

        abort_unless($branchId !== null, 422, 'No active branch.');
        abort_unless(is_super_admin() || auth()->user()?->can($this->permission()), 403);

        $from = isset($input['from']) ? Carbon::parse($input['from'])->startOfDay() : now()->startOfMonth();
        $to   = isset($input['to']) ? Carbon::parse($input['to'])->endOfDay() : now()->endOfDay();

        $cf = $this->cashFlow->getCashFlowStatement($from, $to, $branchId);

        $num = fn ($v) => round((float) $v, 2);

        return [
            'branch'        => current_branch()?->name,
            'from'          => $from->toDateString(),
            'to'            => $to->toDateString(),
            'operating'     => $num($cf['operating']['total'] ?? 0),
            'investing'     => $num($cf['investing']['total'] ?? 0),
            'financing'     => $num($cf['financing']['total'] ?? 0),
            'net_change'    => $num($cf['net_change'] ?? 0),
            'opening_cash'  => $num($cf['opening_cash'] ?? 0),
            'closing_cash'  => $num($cf['closing_cash'] ?? 0),
        ];
    }
}

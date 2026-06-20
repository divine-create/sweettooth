<?php

namespace App\Services\Chatbot\Tools;

use App\Models\CashPosition;
use App\Services\Chatbot\Contracts\ChatTool;
use Carbon\Carbon;

/**
 * Read-only physical-cash position for the CURRENT branch (by cash type),
 * as of the latest recorded position date. Branch-scoped query (the
 * CashPositionService helpers sum across all branches, so we query directly).
 */
class GetCashPositionTool implements ChatTool
{
    public function name(): string
    {
        return 'get_cash_position';
    }

    public function description(): string
    {
        return 'Get the current cash on hand for this branch, broken down by cash '
            . 'type (e.g. till, safe), as of the most recent recorded position. Use '
            . 'for "how much cash do we have?" or cash-on-hand questions.';
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
                'date' => ['type' => 'string', 'description' => 'As-of date (YYYY-MM-DD). Defaults to the latest available.'],
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

        $asOf = isset($input['date']) ? Carbon::parse($input['date'])->toDateString() : now()->toDateString();

        $latest = CashPosition::where('branch_id', $branchId)
            ->where('position_date', '<=', $asOf)
            ->max('position_date');

        if (! $latest) {
            return ['branch' => current_branch()?->name, 'note' => 'No cash positions recorded for this branch.'];
        }

        $rows = CashPosition::where('branch_id', $branchId)
            ->where('position_date', $latest)
            ->get(['cash_type', 'closing_balance', 'physical_count', 'variance_amount']);

        return [
            'branch'  => current_branch()?->name,
            'as_of'   => Carbon::parse($latest)->toDateString(),
            'by_type' => $rows->map(fn ($r) => [
                'cash_type'       => $r->cash_type,
                'closing_balance' => round((float) $r->closing_balance, 2),
                'physical_count'  => $r->physical_count !== null ? round((float) $r->physical_count, 2) : null,
                'variance'        => $r->variance_amount !== null ? round((float) $r->variance_amount, 2) : null,
            ])->all(),
            'total_cash' => round((float) $rows->sum('closing_balance'), 2),
        ];
    }
}

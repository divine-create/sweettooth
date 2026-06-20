<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ProductionOrder;
use App\Services\Chatbot\Contracts\ChatTool;

/**
 * Read-only production-order status overview for the CURRENT branch.
 */
class GetProductionOrdersTool implements ChatTool
{
    public function name(): string
    {
        return 'get_production_orders';
    }

    public function description(): string
    {
        return 'Get production orders for the current branch: a count by status and '
            . 'the most recent orders with their product, quantity, status and planned '
            . 'date. Use for questions about what is in production, pending batches, or '
            . 'production order status. Optionally filter by a status.';
    }

    public function permission(): ?string
    {
        return 'view-production';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'status' => ['type' => 'string', 'description' => 'Optional status filter, e.g. pending, in_progress, completed.'],
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

        $status = trim((string) ($input['status'] ?? ''));

        $statusSummary = ProductionOrder::query()
            ->where('branch_id', $branchId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        $recent = ProductionOrder::query()
            ->with('outputProduct:id,name')
            ->where('branch_id', $branchId)
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByDesc('planned_date')
            ->limit(10)
            ->get();

        return [
            'branch'         => current_branch()?->name,
            'status_summary' => $statusSummary,
            'recent_orders'  => $recent->map(fn (ProductionOrder $o) => [
                'order_number'    => $o->order_number,
                'product'         => $o->outputProduct?->name,
                'output_quantity' => (float) $o->output_quantity,
                'status'          => $o->status,
                'planned_date'    => optional($o->planned_date)->toDateString(),
                'total_cost'      => round((float) $o->total_cost, 2),
            ])->all(),
        ];
    }
}

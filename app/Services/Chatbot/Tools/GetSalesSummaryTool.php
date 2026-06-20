<?php

namespace App\Services\Chatbot\Tools;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Chatbot\Contracts\ChatTool;
use Carbon\Carbon;

/**
 * Read-only sales aggregate for the CURRENT branch over a date range.
 */
class GetSalesSummaryTool implements ChatTool
{
    /** Statuses that should not count toward sales totals. */
    private const EXCLUDED_STATUSES = ['cancelled', 'canceled', 'voided', 'void', 'refunded'];

    public function name(): string
    {
        return 'get_sales_summary';
    }

    public function description(): string
    {
        return 'Get total sales revenue, transaction count, average sale value, and '
            . 'the top-selling products for the current branch over a date range. '
            . 'Use for questions about revenue, takings, or sales performance. '
            . 'Dates must be YYYY-MM-DD.';
    }

    public function permission(): ?string
    {
        return 'view-sales-reports';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'from' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD), inclusive.'],
                'to'   => ['type' => 'string', 'description' => 'End date (YYYY-MM-DD), inclusive.'],
            ],
            'required' => ['from', 'to'],
            'additionalProperties' => false,
        ];
    }

    public function handle(array $input): array
    {
        // Branch is taken from the session — never from the model.
        $branchId = current_branch_id();

        // Defensive re-check at execution time.
        abort_unless($branchId !== null, 422, 'No active branch.');
        abort_unless(auth()->user()?->can($this->permission()), 403);

        $from = Carbon::parse($input['from'])->startOfDay();
        $to   = Carbon::parse($input['to'])->endOfDay();

        $base = Sale::query()
            ->where('branch_id', $branchId)
            ->whereBetween('sale_time', [$from, $to])
            ->whereNotIn('status', self::EXCLUDED_STATUSES);

        $totals = (clone $base)
            ->selectRaw('COUNT(*) as txn_count, COALESCE(SUM(total), 0) as revenue, COALESCE(SUM(tax), 0) as tax')
            ->first();

        $count   = (int) ($totals->txn_count ?? 0);
        $revenue = (float) ($totals->revenue ?? 0);

        $topItems = SaleItem::query()
            ->whereIn('sale_id', (clone $base)->select('id'))
            ->whereNotNull('product_id')
            ->selectRaw('product_id, SUM(quantity) as qty, SUM(total) as revenue')
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'product'  => Product::find($row->product_id)?->name ?? 'Unknown',
                'quantity' => (float) $row->qty,
                'revenue'  => round((float) $row->revenue, 2),
            ])
            ->all();

        return [
            'branch'            => current_branch()?->name,
            'from'              => $from->toDateString(),
            'to'                => $to->toDateString(),
            'transaction_count' => $count,
            'total_revenue'     => round($revenue, 2),
            'total_tax'         => round((float) ($totals->tax ?? 0), 2),
            'average_sale'      => $count > 0 ? round($revenue / $count, 2) : 0,
            'top_products'      => $topItems,
        ];
    }
}

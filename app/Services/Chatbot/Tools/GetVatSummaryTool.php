<?php

namespace App\Services\Chatbot\Tools;

use App\Models\GlEntry;
use App\Services\Accounting\Concerns\ResolvesAccountDefaults;
use App\Services\Chatbot\Contracts\ChatTool;
use Carbon\Carbon;

/**
 * Read-only VAT (sales tax) summary for the CURRENT branch over a date range.
 * Reads the posted GL activity on the configured `sales_tax_payable` account
 * (VAT is credited as it's collected). Supports the monthly FIRS remittance.
 */
class GetVatSummaryTool implements ChatTool
{
    use ResolvesAccountDefaults;

    public function name(): string
    {
        return 'get_vat_summary';
    }

    public function description(): string
    {
        return 'Summarise VAT (sales tax) collected for the current branch over a '
            . 'period, from the posted general-ledger activity on the Sales Tax '
            . 'Payable account. Returns VAT collected, VAT remitted/paid out, and '
            . 'the net VAT still owed. Use for VAT collected questions or the '
            . 'monthly FIRS remittance. Dates default to the current month.';
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
                'from' => ['type' => 'string', 'description' => 'Start date (YYYY-MM-DD). Defaults to the first day of the current month.'],
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

        $from = isset($input['from'])
            ? Carbon::parse($input['from'])->startOfDay()
            : now()->startOfMonth();
        $to = isset($input['to'])
            ? Carbon::parse($input['to'])->endOfDay()
            : now()->endOfDay();

        $vatAccount = $this->resolveAccount('sales_tax_payable', $branchId);

        $rows = GlEntry::query()
            ->where('branch_id', $branchId)
            ->where('gl_account_id', $vatAccount->id)
            ->where('status', 'posted')
            ->whereBetween('entry_date', [$from, $to])
            ->selectRaw('COALESCE(SUM(credit),0) as total_credit, COALESCE(SUM(debit),0) as total_debit')
            ->first();

        $collected = round((float) ($rows->total_credit ?? 0), 2); // VAT charged on sales
        $remitted  = round((float) ($rows->total_debit ?? 0), 2);   // VAT paid out / reversed

        return [
            'branch'       => current_branch()?->name,
            'account'      => $vatAccount->account_number . ' — ' . $vatAccount->account_name,
            'from'         => $from->toDateString(),
            'to'           => $to->toDateString(),
            'vat_collected' => $collected,
            'vat_remitted'  => $remitted,
            'net_vat_due'   => round($collected - $remitted, 2),
            'note'          => ($collected === 0.0 && $remitted === 0.0)
                ? 'No posted VAT activity in this period.'
                : 'Net VAT due is collected minus remitted for the period.',
        ];
    }
}

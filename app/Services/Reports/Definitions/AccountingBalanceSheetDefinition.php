<?php

namespace App\Services\Reports\Definitions;

use App\Services\Reports\BalanceSheetService;
use Carbon\Carbon;

class AccountingBalanceSheetDefinition implements ReportDefinition
{
    public function meta(): array
    {
        return [
            'name' => 'Balance Sheet',
            'type' => 'balance_sheet',
            'category' => 'accounting',
            'requires_department' => false,
            'permissions' => [],
        ];
    }

    public function query(array $context): array
    {
        $branchId = $context['branch_id'] ?? null;
        $asOf = $context['period_to'] ?? null;

        $service = new BalanceSheetService();
        $report = $service->generate(
            null,
            $asOf ? Carbon::parse($asOf) : null,
            $branchId
        );

        $report['period_info'] = [
            'as_of' => $asOf,
        ];

        return $report;
    }

    public function summary(array $data, array $context): array
    {
        return [
            'total_assets' => $data['totals']['total_assets'] ?? 0,
            'total_liabilities' => $data['totals']['total_liabilities'] ?? 0,
            'total_equity' => $data['totals']['total_equity'] ?? 0,
            'total_liabilities_equity' => $data['totals']['total_liabilities_equity'] ?? 0,
            'is_balanced' => $data['balance_check']['is_balanced'] ?? false,
            'balance_difference' => $data['balance_check']['difference'] ?? 0,
        ];
    }

    public function charts(array $data, array $context): array
    {
        return [
            'balance_sheet_chart' => [
                'type' => 'bar',
                'labels' => ['Assets', 'Liabilities', 'Equity'],
                'datasets' => [
                    [
                        'label' => 'Amount',
                        'data' => [
                            $data['totals']['total_assets'] ?? 0,
                            $data['totals']['total_liabilities'] ?? 0,
                            $data['totals']['total_equity'] ?? 0,
                        ],
                        'color' => '#2563eb',
                    ],
                ],
            ],
        ];
    }

    public function tables(array $data, array $summary, array $context): array
    {
        $makeRows = function ($items) {
            return array_map(function ($row) {
                return [
                    $row['account_number'] ?? '',
                    $row['account_name'] ?? '',
                    $row['debit'] ?? 0,
                    $row['credit'] ?? 0,
                    $row['balance'] ?? 0,
                ];
            }, $items ?? []);
        };

        return [
            'assets' => [
                'headers' => ['Account #', 'Account', 'Debit', 'Credit', 'Balance'],
                'rows' => $makeRows($data['assets']['items'] ?? []),
            ],
            'liabilities' => [
                'headers' => ['Account #', 'Account', 'Debit', 'Credit', 'Balance'],
                'rows' => $makeRows($data['liabilities']['items'] ?? []),
            ],
            'equity' => [
                'headers' => ['Account #', 'Account', 'Debit', 'Credit', 'Balance'],
                'rows' => $makeRows($data['equity']['items'] ?? []),
            ],
        ];
    }

    public function narrative(array $data, array $summary, array $context): array
    {
        $highlights = [];
        $concerns = [];

        if (!empty($summary['is_balanced'])) {
            $highlights[] = 'Balance sheet is balanced for the selected date.';
        } else {
            $concerns[] = 'Balance sheet is out of balance. Review account postings.';
        }

        return [
            'overview' => 'Total assets of ' . number_format($summary['total_assets'] ?? 0, 2)
                . ' vs total liabilities and equity of ' . number_format($summary['total_liabilities_equity'] ?? 0, 2) . '.',
            'highlights' => $highlights,
            'concerns' => $concerns,
            'recommendations' => [
                'Reconcile account balances for large variances.',
                'Review asset and liability composition for changes.',
            ],
        ];
    }
}

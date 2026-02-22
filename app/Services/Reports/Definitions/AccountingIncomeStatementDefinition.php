<?php

namespace App\Services\Reports\Definitions;

use App\Services\Reports\IncomeStatementService;
use Carbon\Carbon;

class AccountingIncomeStatementDefinition implements ReportDefinition
{
    public function meta(): array
    {
        return [
            'name' => 'Income Statement',
            'type' => 'income_statement',
            'category' => 'accounting',
            'requires_department' => false,
            'permissions' => [],
        ];
    }

    public function query(array $context): array
    {
        $branchId = $context['branch_id'] ?? null;
        $from = $context['period_from'] ?? null;
        $to = $context['period_to'] ?? null;

        $service = new IncomeStatementService();
        $report = $service->generate(
            null,
            $from ? Carbon::parse($from) : null,
            $to ? Carbon::parse($to) : null,
            $branchId
        );

        $report['period_info'] = [
            'from' => $from,
            'to' => $to,
        ];

        return $report;
    }

    public function summary(array $data, array $context): array
    {
        return [
            'total_revenue' => $data['revenue']['total'] ?? 0,
            'total_cogs' => $data['cost_of_goods_sold']['total'] ?? 0,
            'gross_profit' => $data['gross_profit'] ?? 0,
            'total_expenses' => $data['operating_expenses']['total'] ?? 0,
            'operating_income' => $data['operating_income'] ?? 0,
            'net_income' => $data['net_income'] ?? 0,
            'gross_margin' => $data['summary']['gross_margin'] ?? 0,
            'operating_margin' => $data['summary']['operating_margin'] ?? 0,
            'net_margin' => $data['summary']['net_margin'] ?? 0,
        ];
    }

    public function charts(array $data, array $context): array
    {
        return [
            'profitability_chart' => [
                'type' => 'bar',
                'labels' => ['Revenue', 'COGS', 'Operating Expenses', 'Net Income'],
                'datasets' => [
                    [
                        'label' => 'Amount',
                        'data' => [
                            $data['revenue']['total'] ?? 0,
                            $data['cost_of_goods_sold']['total'] ?? 0,
                            $data['operating_expenses']['total'] ?? 0,
                            $data['net_income'] ?? 0,
                        ],
                        'color' => '#3b82f6',
                    ],
                ],
            ],
            'margin_chart' => [
                'type' => 'bar',
                'labels' => ['Gross Margin %', 'Operating Margin %', 'Net Margin %'],
                'datasets' => [
                    [
                        'label' => 'Margin',
                        'data' => [
                            $data['summary']['gross_margin'] ?? 0,
                            $data['summary']['operating_margin'] ?? 0,
                            $data['summary']['net_margin'] ?? 0,
                        ],
                        'color' => '#10b981',
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
            'revenue_accounts' => [
                'headers' => ['Account #', 'Account', 'Debit', 'Credit', 'Balance'],
                'rows' => $makeRows($data['revenue']['items'] ?? []),
            ],
            'cogs_accounts' => [
                'headers' => ['Account #', 'Account', 'Debit', 'Credit', 'Balance'],
                'rows' => $makeRows($data['cost_of_goods_sold']['items'] ?? []),
            ],
            'operating_expenses' => [
                'headers' => ['Account #', 'Account', 'Debit', 'Credit', 'Balance'],
                'rows' => $makeRows($data['operating_expenses']['items'] ?? []),
            ],
            'other_income' => [
                'headers' => ['Account #', 'Account', 'Debit', 'Credit', 'Balance'],
                'rows' => $makeRows($data['other_income']['items'] ?? []),
            ],
            'other_expenses' => [
                'headers' => ['Account #', 'Account', 'Debit', 'Credit', 'Balance'],
                'rows' => $makeRows($data['other_expenses']['items'] ?? []),
            ],
        ];
    }

    public function narrative(array $data, array $summary, array $context): array
    {
        $highlights = [];
        $concerns = [];

        if (($summary['net_income'] ?? 0) > 0) {
            $highlights[] = 'Net income is positive for the selected period.';
        }

        if (($summary['net_income'] ?? 0) < 0) {
            $concerns[] = 'Net income is negative. Review revenue and expense drivers.';
        }

        return [
            'overview' => 'Income statement generated with total revenue of ' . number_format($summary['total_revenue'] ?? 0, 2)
                . ' and net income of ' . number_format($summary['net_income'] ?? 0, 2) . '.',
            'highlights' => $highlights,
            'concerns' => $concerns,
            'recommendations' => [
                'Review top revenue and expense accounts for performance drivers.',
                'Monitor margins and cost structure for improvements.',
            ],
        ];
    }
}

<?php

namespace App\Services\Reports\Definitions;

use App\Services\Reports\TrialBalanceService;
use Carbon\Carbon;

class AccountingTrialBalanceDefinition implements ReportDefinition
{
    public function meta(): array
    {
        return [
            'name' => 'Trial Balance',
            'type' => 'trial_balance',
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

        $service = new TrialBalanceService();
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
            'total_debits' => $data['summary']['total_debits'] ?? 0,
            'total_credits' => $data['summary']['total_credits'] ?? 0,
            'is_balanced' => $data['summary']['is_balanced'] ?? false,
            'difference' => $data['summary']['difference'] ?? 0,
            'accounts_count' => count($data['accounts'] ?? []),
        ];
    }

    public function charts(array $data, array $context): array
    {
        return [
            'trial_balance_chart' => [
                'type' => 'bar',
                'labels' => ['Total Debits', 'Total Credits'],
                'datasets' => [
                    [
                        'label' => 'Amount',
                        'data' => [
                            $data['summary']['total_debits'] ?? 0,
                            $data['summary']['total_credits'] ?? 0,
                        ],
                        'color' => '#f97316',
                    ],
                ],
            ],
        ];
    }

    public function tables(array $data, array $summary, array $context): array
    {
        return [
            'trial_balance_accounts' => [
                'headers' => ['Account #', 'Account', 'Type', 'Debit', 'Credit'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['account_number'] ?? '',
                        $row['account_name'] ?? '',
                        $row['account_type'] ?? '',
                        $row['debit'] ?? 0,
                        $row['credit'] ?? 0,
                    ];
                }, $data['accounts'] ?? []),
            ],
        ];
    }

    public function narrative(array $data, array $summary, array $context): array
    {
        $highlights = [];
        $concerns = [];

        if (!empty($summary['is_balanced'])) {
            $highlights[] = 'Trial balance is balanced for the selected period.';
        } else {
            $concerns[] = 'Trial balance is out of balance. Review postings and account mappings.';
        }

        return [
            'overview' => 'Trial balance includes ' . number_format($summary['accounts_count'] ?? 0) . ' accounts with total debits of '
                . number_format($summary['total_debits'] ?? 0, 2) . ' and total credits of '
                . number_format($summary['total_credits'] ?? 0, 2) . '.',
            'highlights' => $highlights,
            'concerns' => $concerns,
            'recommendations' => [
                'Investigate any imbalance before closing the period.',
                'Validate large debit or credit accounts for accuracy.',
            ],
        ];
    }
}

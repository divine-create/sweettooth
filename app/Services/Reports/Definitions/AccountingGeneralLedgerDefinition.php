<?php

namespace App\Services\Reports\Definitions;

use App\Services\Reports\GeneralLedgerService;
use Carbon\Carbon;

class AccountingGeneralLedgerDefinition implements ReportDefinition
{
    private const ENTRY_LIMIT = 200;

    public function meta(): array
    {
        return [
            'name' => 'General Ledger',
            'type' => 'general_ledger',
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

        $service = new GeneralLedgerService();
        $ledger = $service->getFullLedger(
            null,
            $from ? Carbon::parse($from) : null,
            $to ? Carbon::parse($to) : null,
            $branchId
        );

        $summary = $service->getSummary(
            null,
            $from ? Carbon::parse($from) : null,
            $to ? Carbon::parse($to) : null,
            $branchId
        )->map(function ($row) {
            $account = $row['account'] ?? null;

            return [
                'account_number' => $account?->account_number ?? '',
                'account_name' => $account?->account_name ?? '',
                'account_type' => $account?->account_type ?? '',
                'total_debit' => $row['total_debit'] ?? 0,
                'total_credit' => $row['total_credit'] ?? 0,
                'balance' => $row['balance'] ?? 0,
            ];
        })->values()->all();

        return [
            'ledger' => $ledger,
            'summary' => $summary,
            'period_info' => [
                'from' => $from,
                'to' => $to,
            ],
        ];
    }

    public function summary(array $data, array $context): array
    {
        $totalDebit = 0;
        $totalCredit = 0;
        $summary = $data['summary'] ?? [];

        foreach ($summary as $row) {
            $totalDebit += $row['total_debit'] ?? 0;
            $totalCredit += $row['total_credit'] ?? 0;
        }

        $difference = $totalDebit - $totalCredit;

        return [
            'total_accounts' => count($summary),
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'difference' => $difference,
            'is_balanced' => abs($difference) < 0.01,
        ];
    }

    public function charts(array $data, array $context): array
    {
        $summary = $data['summary'] ?? [];
        $topAccounts = array_slice($summary, 0, 10);

        return [
            'top_accounts_chart' => [
                'type' => 'bar',
                'labels' => array_map(function ($row) {
                    return $row['account_name'] ?? 'Account';
                }, $topAccounts),
                'datasets' => [
                    [
                        'label' => 'Balance',
                        'data' => array_map(function ($row) {
                            return $row['balance'] ?? 0;
                        }, $topAccounts),
                        'color' => '#0ea5e9',
                    ],
                ],
            ],
        ];
    }

    public function tables(array $data, array $summary, array $context): array
    {
        $summaryRows = array_map(function ($row) {
            return [
                $row['account_number'] ?? '',
                $row['account_name'] ?? '',
                $row['account_type'] ?? '',
                $row['total_debit'] ?? 0,
                $row['total_credit'] ?? 0,
                $row['balance'] ?? 0,
            ];
        }, $data['summary'] ?? []);

        $totalEntries = 0;
        $entries = $this->flattenLedgerEntries($data['ledger'] ?? [], self::ENTRY_LIMIT, $totalEntries);

        return [
            'accounts_summary' => [
                'headers' => ['Account #', 'Account', 'Type', 'Total Debit', 'Total Credit', 'Balance'],
                'rows' => $summaryRows,
            ],
            'ledger_entries' => [
                'headers' => ['Account #', 'Account', 'Date', 'Reference', 'Description', 'Debit', 'Credit', 'Balance'],
                'rows' => $entries,
            ],
        ];
    }

    public function narrative(array $data, array $summary, array $context): array
    {
        $highlights = [];
        $concerns = [];

        if (!empty($summary['is_balanced'])) {
            $highlights[] = 'General ledger totals are balanced.';
        } else {
            $concerns[] = 'General ledger totals are not balanced. Review posting accuracy.';
        }

        $entryCount = $this->countLedgerEntries($data['ledger'] ?? []);
        if ($entryCount > self::ENTRY_LIMIT) {
            $concerns[] = 'Ledger entries table is truncated to the first ' . self::ENTRY_LIMIT . ' rows.';
        }

        return [
            'overview' => 'General ledger covers ' . number_format($summary['total_accounts'] ?? 0) . ' accounts with total debits of '
                . number_format($summary['total_debit'] ?? 0, 2) . ' and total credits of '
                . number_format($summary['total_credit'] ?? 0, 2) . '.',
            'highlights' => $highlights,
            'concerns' => $concerns,
            'recommendations' => [
                'Audit high-activity accounts for posting accuracy.',
                'Resolve any differences between total debits and credits.',
            ],
        ];
    }

    private function flattenLedgerEntries(array $ledger, int $limit, int &$totalCount = 0): array
    {
        $rows = [];
        $totalCount = 0;

        foreach ($ledger as $accountLedger) {
            $entries = $accountLedger['entries'] ?? [];
            foreach ($entries as $entry) {
                $totalCount++;
                if (count($rows) >= $limit) {
                    continue;
                }

                $rows[] = [
                    $accountLedger['account_number'] ?? '',
                    $accountLedger['account_name'] ?? '',
                    $entry['date'] ?? '',
                    $entry['reference'] ?? '',
                    $entry['description'] ?? '',
                    $entry['debit'] ?? 0,
                    $entry['credit'] ?? 0,
                    $entry['balance'] ?? 0,
                ];
            }
        }

        return $rows;
    }

    private function countLedgerEntries(array $ledger): int
    {
        $count = 0;
        foreach ($ledger as $accountLedger) {
            $count += count($accountLedger['entries'] ?? []);
        }

        return $count;
    }
}

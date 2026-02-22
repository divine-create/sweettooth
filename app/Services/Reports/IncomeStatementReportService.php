<?php

namespace App\Services\Reports;

use Carbon\Carbon;

class IncomeStatementReportService extends ReportService
{
    protected string $reportCategory = 'accounting';
    protected string $reportType = 'income_statement';

    protected function getReportName(): string
    {
        return 'Income Statement Report';
    }

    protected function generateReportData(): array
    {
        $this->validateParameters();

        $service = new IncomeStatementService();

        return $service->generate(
            null,
            $this->periodFrom ? Carbon::parse($this->periodFrom) : null,
            $this->periodTo ? Carbon::parse($this->periodTo) : null,
            $this->branchId
        );
    }

    protected function generateSummaryMetrics(array $reportData): array
    {
        return $reportData['summary'] ?? [];
    }

    protected function generateChartsData(array $reportData): array
    {
        return [];
    }
}

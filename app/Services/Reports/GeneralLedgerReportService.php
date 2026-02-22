<?php

namespace App\Services\Reports;

use Carbon\Carbon;

class GeneralLedgerReportService extends ReportService
{
    protected string $reportCategory = 'accounting';
    protected string $reportType = 'general_ledger';

    protected function getReportName(): string
    {
        return 'General Ledger Report';
    }

    protected function generateReportData(): array
    {
        $this->validateParameters();

        $service = new GeneralLedgerService();

        return $service->export(
            null,
            $this->periodFrom ? Carbon::parse($this->periodFrom) : null,
            $this->periodTo ? Carbon::parse($this->periodTo) : null,
            $this->branchId
        );
    }

    protected function generateSummaryMetrics(array $reportData): array
    {
        return [];
    }

    protected function generateChartsData(array $reportData): array
    {
        return [];
    }
}

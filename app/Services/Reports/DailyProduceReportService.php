<?php

namespace App\Services\Reports;

use Carbon\Carbon;

class DailyProduceReportService extends ReportService
{
    protected string $reportCategory = 'production';
    protected string $reportType = 'daily_produce';

    protected function getReportName(): string
    {
        return 'Daily Produce Report';
    }

    protected function generateReportData(): array
    {
        $this->validateParameters();

        $definition = new \App\Services\Reports\Definitions\ProductionDailyProduceDefinition();
        $payload = $definition->query([
            'branch_id' => $this->branchId,
            'department_id' => $this->departmentId,
            'period_from' => $this->periodFrom,
            'period_to' => $this->periodTo,
        ]);

        $payload['summary_metrics'] = $definition->summary($payload, [
            'branch_id' => $this->branchId,
            'department_id' => $this->departmentId,
            'period_from' => $this->periodFrom,
            'period_to' => $this->periodTo,
        ]);

        return $payload;
    }

    protected function generateSummaryMetrics(array $reportData): array
    {
        return $reportData['summary_metrics'] ?? [];
    }

    protected function generateChartsData(array $reportData): array
    {
        return [];
    }
}

<?php

namespace App\Services\Reports;

use App\Services\Reports\Definitions\HRLeaveUtilizationDefinition;

class HRLeaveUtilizationReportService extends ReportService
{
    protected string $reportCategory = 'hr';
    protected string $reportType = 'leave_utilization';

    protected function getReportName(): string
    {
        return 'HR Leave Utilization Report';
    }

    protected function generateReportData(): array
    {
        $this->validateParameters();

        $definition = new HRLeaveUtilizationDefinition();

        return $definition->query([
            'branch_id' => $this->branchId,
            'department_id' => $this->departmentId,
            'period_from' => $this->periodFrom,
            'period_to' => $this->periodTo,
            'actor' => current_actor(),
        ]);
    }

    protected function generateSummaryMetrics(array $reportData): array
    {
        $definition = new HRLeaveUtilizationDefinition();

        return $definition->summary($reportData, [
            'branch_id' => $this->branchId,
            'department_id' => $this->departmentId,
            'period_from' => $this->periodFrom,
            'period_to' => $this->periodTo,
        ]);
    }

    protected function generateChartsData(array $reportData): array
    {
        $definition = new HRLeaveUtilizationDefinition();

        return $definition->charts($reportData, [
            'branch_id' => $this->branchId,
            'department_id' => $this->departmentId,
            'period_from' => $this->periodFrom,
            'period_to' => $this->periodTo,
        ]);
    }
}

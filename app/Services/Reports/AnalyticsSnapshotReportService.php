<?php

namespace App\Services\Reports;

use App\Models\Department;
use App\Models\DepartmentReport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AnalyticsSnapshotReportService
{
    /**
     * Generate and persist an analytics snapshot as a department report.
     */
    public function generate(array $payload): DepartmentReport
    {
        $branchId = $payload['branch_id'] ?? current_branch_id();
        if (!$branchId) {
            throw new InvalidArgumentException('Branch ID is required to generate report.');
        }

        $reportType = $payload['report_type'] ?? null;
        if (!$reportType) {
            throw new InvalidArgumentException('Report type is required.');
        }

        $reportData = $this->normalizeReportData($payload['report_data'] ?? []);
        $summaryMetrics = $payload['summary_metrics'] ?? [];
        $chartsData = $payload['charts_data'] ?? [];

        $periodFrom = $payload['period_from'] ?? now()->toDateString();
        $periodTo = $payload['period_to'] ?? $periodFrom;

        $actor = current_actor();
        $generatedById = $actor?->getKey() ?? Auth::id();
        $generatedByType = $actor ? get_class($actor) : null;

        $departmentId = $payload['department_id'] ?? $this->resolveDepartmentId($branchId);

        return DB::transaction(function () use (
            $branchId,
            $departmentId,
            $generatedById,
            $generatedByType,
            $reportType,
            $reportData,
            $summaryMetrics,
            $chartsData,
            $periodFrom,
            $periodTo,
            $payload
        ) {
            return DepartmentReport::create([
                'branch_id' => $branchId,
                'department_id' => $departmentId,
                'generated_by_id' => $generatedById,
                'generated_by_type' => $generatedByType,
                'report_type' => $reportType,
                'report_category' => $payload['report_category'] ?? 'inventory',
                'report_name' => $payload['report_name'] ?? $this->defaultReportName($reportType),
                'report_date' => now()->toDateString(),
                'period_from' => $periodFrom,
                'period_to' => $periodTo,
                'report_data' => $reportData,
                'summary_metrics' => $summaryMetrics,
                'charts_data' => $chartsData,
                'system_data_hash' => DepartmentReport::computeSystemDataHash(
                    $reportData,
                    $summaryMetrics,
                    $chartsData
                ),
                'system_data_version' => 1,
                'system_data_locked_at' => now(),
                'status' => $payload['status'] ?? 'pending_review',
            ]);
        });
    }

    private function resolveDepartmentId(string $branchId): ?int
    {
        $selectedDepartmentId = session('selected_department_id');
        if ($selectedDepartmentId
            && Department::where('id', $selectedDepartmentId)->where('branch_id', $branchId)->exists()) {
            return (int) $selectedDepartmentId;
        }

        $actorDepartmentId = current_actor()?->department_id;
        if ($actorDepartmentId
            && Department::where('id', $actorDepartmentId)->where('branch_id', $branchId)->exists()) {
            return (int) $actorDepartmentId;
        }

        return Department::where('branch_id', $branchId)->orderBy('name')->value('id');
    }

    private function normalizeReportData(array $reportData): array
    {
        if (!isset($reportData['tables']) && isset($reportData['table_rows']) && is_array($reportData['table_rows'])) {
            $rows = $this->normalizeRows($reportData['table_rows']);
            if (!empty($rows)) {
                $headers = array_keys(reset($rows));
                $reportData['tables'] = [
                    'table_rows' => [
                        'headers' => array_map(
                            fn (string $header) => str($header)->replace('_', ' ')->title()->toString(),
                            $headers
                        ),
                        'rows' => array_map(
                            fn (array $row) => array_values(
                                array_map(fn (string $header) => $row[$header] ?? null, $headers)
                            ),
                            $rows
                        ),
                    ],
                ];
            }
        }

        return $reportData;
    }

    private function normalizeRows(array $rows): array
    {
        $normalized = [];
        foreach ($rows as $row) {
            if (!is_array($row) || $row === []) {
                continue;
            }

            // Keep only rows that can be rendered as a simple scalar table.
            $isRenderable = collect($row)->every(
                fn ($value) => is_null($value) || is_scalar($value)
            );
            if (!$isRenderable) {
                continue;
            }

            $normalized[] = $row;
        }

        return $normalized;
    }

    private function defaultReportName(string $reportType): string
    {
        return str($reportType)->replace('_', ' ')->title()->toString() . ' Report';
    }
}

<?php

namespace App\Services\Chatbot\Tools;

use App\Services\Chatbot\Contracts\ChatTool;
use App\Services\Reports\ReportRegistry;
use Carbon\Carbon;

/**
 * Runs a registered analytical report (read-only) for the CURRENT branch and
 * returns its summary metrics and narrative. Uses ReportService::getReportData()
 * which computes/caches WITHOUT writing a DepartmentReport row. Per-report
 * permission + branch scoping are enforced by ReportRegistry::resolve().
 */
class RunReportTool implements ChatTool
{
    public function name(): string
    {
        return 'run_report';
    }

    public function description(): string
    {
        return 'Run one of the analytical reports (get its key from list_reports) for '
            . 'the current branch and return its summary metrics and narrative '
            . 'insights. Use for deeper analytics such as sales performance, stock '
            . 'valuation, stock movement, production efficiency, waste analysis, cost '
            . 'analysis, or HR workforce overview. Dates are YYYY-MM-DD and default '
            . 'to the current month.';
    }

    public function permission(): ?string
    {
        // Access is enforced per-report inside ReportRegistry::resolve().
        return null;
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'key'  => ['type' => 'string', 'description' => 'Report key from list_reports, e.g. "sales.sales_performance".'],
                'from' => ['type' => 'string', 'description' => 'Period start (YYYY-MM-DD). Defaults to start of this month.'],
                'to'   => ['type' => 'string', 'description' => 'Period end (YYYY-MM-DD). Defaults to today.'],
            ],
            'required' => ['key'],
            'additionalProperties' => false,
        ];
    }

    public function handle(array $input): array
    {
        $branchId = current_branch_id();
        abort_unless($branchId !== null, 422, 'No active branch.');

        $key = trim((string) ($input['key'] ?? ''));

        $report = $key !== '' ? ReportRegistry::resolve($key, auth()->user()) : null;

        if (! $report) {
            return [
                'error' => "Unknown report key '{$key}', or you do not have permission to view it. "
                    . 'Call list_reports for the valid keys.',
            ];
        }

        $from = isset($input['from']) ? Carbon::parse($input['from'])->startOfDay() : now()->startOfMonth();
        $to   = isset($input['to']) ? Carbon::parse($input['to'])->endOfDay() : now()->endOfDay();

        $definitionClass = $report['definition'];
        $serviceClass = $report['service']; // concrete subclass of the abstract ReportService

        try {
            $payload = app($serviceClass)
                ->useDefinition(new $definitionClass)
                ->forBranch($branchId)
                ->forPeriod($from, $to)
                ->getReportData();
        } catch (\Throwable $e) {
            report($e);

            return ['error' => 'The report could not be generated for the given parameters.'];
        }

        return [
            'report'    => $report['meta']['name'] ?? $key,
            'branch'    => current_branch()?->name,
            'period'    => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'summary'   => $payload['summary_metrics'] ?? [],
            'narrative' => $payload['report_data']['narrative'] ?? [],
        ];
    }
}

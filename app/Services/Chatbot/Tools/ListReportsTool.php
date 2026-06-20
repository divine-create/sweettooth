<?php

namespace App\Services\Chatbot\Tools;

use App\Services\Chatbot\Contracts\ChatTool;
use App\Services\Reports\ReportRegistry;

/**
 * Lists the analytical reports the current user is allowed to run, so the model
 * can discover valid keys for run_report. Per-user/permission filtering is done
 * by ReportRegistry itself.
 */
class ListReportsTool implements ChatTool
{
    public function name(): string
    {
        return 'list_reports';
    }

    public function description(): string
    {
        return 'List the analytical reports available to the current user across '
            . 'finance, sales, inventory, production and HR. Returns each report\'s '
            . 'key and name. Call this first to discover what run_report can produce, '
            . 'then call run_report with a chosen key.';
    }

    public function permission(): ?string
    {
        // Access is enforced per-report inside ReportRegistry.
        return null;
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
            'required' => [],
            'additionalProperties' => false,
        ];
    }

    public function handle(array $input): array
    {
        $reports = collect(ReportRegistry::availableForUser(auth()->user()))
            ->map(fn ($r) => [
                'key'      => $r['key'],
                'name'     => $r['meta']['name'] ?? $r['key'],
                'category' => $r['meta']['category'] ?? null,
            ])
            ->values()
            ->all();

        return [
            'count'   => count($reports),
            'reports' => $reports,
            'note'    => $reports === [] ? 'No reports available to this user.' : null,
        ];
    }
}

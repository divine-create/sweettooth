<?php

namespace App\Services\Reports;

use App\Models\User;
use App\Services\Reports\Definitions\AccountingBalanceSheetDefinition;
use App\Services\Reports\Definitions\AccountingGeneralLedgerDefinition;
use App\Services\Reports\Definitions\AccountingIncomeStatementDefinition;
use App\Services\Reports\Definitions\AccountingTrialBalanceDefinition;
use App\Services\Reports\Definitions\HRLeaveUtilizationDefinition;
use App\Services\Reports\Definitions\HRWorkforceOverviewDefinition;
use App\Services\Reports\Definitions\ProductionCostAnalysisDefinition;
use App\Services\Reports\Definitions\ProductionDailyProduceDefinition;
use App\Services\Reports\Definitions\ProductionEfficiencyDefinition;
use App\Services\Reports\Definitions\ProductionQualityDefinition;
use App\Services\Reports\Definitions\ProductionWasteAnalysisDefinition;
use App\Services\Reports\Definitions\SalesPerformanceDefinition;
use App\Services\SidebarVisibilityService;
use Illuminate\Support\Facades\Log;

class ReportRegistry
{
    /**
     * Report definitions mapped to service classes.
     */
    public static function all(): array
    {
        return [
            [
                'definition' => ProductionEfficiencyDefinition::class,
                'service' => ProductionEfficiencyReportService::class,
            ],
            [
                'definition' => ProductionDailyProduceDefinition::class,
                'service' => DailyProduceReportService::class,
            ],
            [
                'definition' => ProductionQualityDefinition::class,
                'service' => ProductionQualityReportService::class,
            ],
            [
                'definition' => ProductionCostAnalysisDefinition::class,
                'service' => CostAnalysisReportService::class,
            ],
            [
                'definition' => ProductionWasteAnalysisDefinition::class,
                'service' => WasteAnalysisReportService::class,
            ],
            [
                'definition' => SalesPerformanceDefinition::class,
                'service' => SalesPerformanceReportService::class,
            ],
            [
                'definition' => AccountingIncomeStatementDefinition::class,
                'service' => IncomeStatementReportService::class,
            ],
            [
                'definition' => AccountingBalanceSheetDefinition::class,
                'service' => BalanceSheetReportService::class,
            ],
            [
                'definition' => AccountingTrialBalanceDefinition::class,
                'service' => TrialBalanceReportService::class,
            ],
            [
                'definition' => AccountingGeneralLedgerDefinition::class,
                'service' => GeneralLedgerReportService::class,
            ],
            [
                'definition' => HRWorkforceOverviewDefinition::class,
                'service' => HRWorkforceOverviewReportService::class,
            ],
            [
                'definition' => HRLeaveUtilizationDefinition::class,
                'service' => HRLeaveUtilizationReportService::class,
            ],
            [
                'definition' => InventoryStockLevelsDefinition::class,
                'service' => InventoryStockLevelsReportService::class,
            ],
            [
                'definition' => InventoryStockValuationDefinition::class,
                'service' => InventoryStockValuationReportService::class,
            ],
            [
                'definition' => InventoryStockMovementDefinition::class,
                'service' => InventoryStockMovementReportService::class,
            ],
            [
                'definition' => InventoryItemUsageDefinition::class,
                'service' => InventoryItemUsageReportService::class,
            ],
        ];
    }

    /**
     * Reports available to a given user (permission-aware).
     */
    public static function availableForUser(?User $user = null): array
    {
        $user = $user ?? auth()->user();
        $reports = [];

        foreach (self::all() as $entry) {
            try {
                $definitionClass = $entry['definition'];
                if (! class_exists($definitionClass)) {
                    continue;
                }

                $definition = new $definitionClass;
                $meta = $definition->meta();
                $key = self::keyFromMeta($meta);

                if (! self::userCanAccess($user, $meta['permissions'] ?? [])) {
                    continue;
                }

                $reports[] = [
                    'key' => $key,
                    'definition' => $definitionClass,
                    'service' => $entry['service'],
                    'meta' => $meta,
                ];
            } catch (\Throwable $e) {
                Log::warning('Skipping invalid report definition entry in registry.', [
                    'definition' => $entry['definition'] ?? null,
                    'service' => $entry['service'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        usort($reports, function ($a, $b) {
            $categorySort = strcmp(($a['meta']['category'] ?? ''), ($b['meta']['category'] ?? ''));
            if ($categorySort !== 0) {
                return $categorySort;
            }

            $orderA = $a['meta']['order'] ?? 999;
            $orderB = $b['meta']['order'] ?? 999;
            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            return strcmp(($a['meta']['name'] ?? ''), ($b['meta']['name'] ?? ''));
        });

        return $reports;
    }

    /**
     * Resolve a report by registry key.
     */
    public static function resolve(string $key, ?User $user = null): ?array
    {
        foreach (self::availableForUser($user) as $report) {
            if ($report['key'] === $key) {
                return $report;
            }
        }

        return null;
    }

    /**
     * Group available reports by category.
     */
    public static function groupedByCategory(?User $user = null): array
    {
        $grouped = [];
        foreach (self::availableForUser($user) as $report) {
            $category = $report['meta']['category'] ?? 'other';
            if (! isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $report;
        }

        ksort($grouped);

        return $grouped;
    }

    private static function keyFromMeta(array $meta): string
    {
        $category = $meta['category'] ?? 'report';
        $type = $meta['type'] ?? 'generic';

        return "{$category}.{$type}";
    }

    private static function userCanAccess(?User $user, array $permissions): bool
    {
        if (! $user) {
            return false;
        }

        if (SidebarVisibilityService::isAdmin($user)) {
            return true;
        }

        if (empty($permissions)) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
            $altUnderscore = str_replace('-', '_', $permission);
            if ($altUnderscore !== $permission && $user->can($altUnderscore)) {
                return true;
            }
            $altHyphen = str_replace('_', '-', $permission);
            if ($altHyphen !== $permission && $user->can($altHyphen)) {
                return true;
            }
        }

        return false;
    }
}

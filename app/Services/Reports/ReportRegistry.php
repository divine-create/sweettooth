<?php

namespace App\Services\Reports;

use App\Models\User;
use App\Services\Reports\Definitions\AccountingApAgingDefinition;
use App\Services\Reports\Definitions\AccountingArAgingDefinition;
use App\Services\Reports\Definitions\AccountingBalanceSheetDefinition;
use App\Services\Reports\Definitions\AccountingBankStatementExceptionDefinition;
use App\Services\Reports\Definitions\AccountingBudgetVsActualDefinition;
use App\Services\Reports\Definitions\AccountingCashFlowDefinition;
use App\Services\Reports\Definitions\AccountingCashPositionDefinition;
use App\Services\Reports\Definitions\AccountingExpenseClaimsDefinition;
use App\Services\Reports\Definitions\AccountingFixedAssetRegisterDefinition;
use App\Services\Reports\Definitions\AccountingGeneralLedgerDefinition;
use App\Services\Reports\Definitions\AccountingIncomeStatementDefinition;
use App\Services\Reports\Definitions\AccountingSupplierInvoiceReconciliationDefinition;
use App\Services\Reports\Definitions\AccountingTrialBalanceDefinition;
use App\Services\Reports\Definitions\HRAppraisalRatingsDefinition;
use App\Services\Reports\Definitions\HRAttendancePunctualityDefinition;
use App\Services\Reports\Definitions\HRDepartmentStaffingWorkloadDefinition;
use App\Services\Reports\Definitions\HRLeaveBalanceAccrualDefinition;
use App\Services\Reports\Definitions\HRLeaveUtilizationDefinition;
use App\Services\Reports\Definitions\HRPayrollCompensationDefinition;
use App\Services\Reports\Definitions\HRTurnoverRetentionDefinition;
use App\Services\Reports\Definitions\HRWorkforceOverviewDefinition;
use App\Services\Reports\Definitions\InventoryExpiryDeadStockDefinition;
use App\Services\Reports\Definitions\InventoryInterBranchTransferDefinition;
use App\Services\Reports\Definitions\InventoryItemUsageDefinition;
use App\Services\Reports\Definitions\InventoryReserveAllocationDefinition;
use App\Services\Reports\Definitions\InventorySeasonalDemandDefinition;
use App\Services\Reports\Definitions\InventoryStockLevelsDefinition;
use App\Services\Reports\Definitions\InventoryStockMovementDefinition;
use App\Services\Reports\Definitions\InventoryStockValuationDefinition;
use App\Services\Reports\Definitions\InventoryStockVarianceDiscrepancyDefinition;
use App\Services\Reports\Definitions\InventorySupplierPerformanceDefinition;
use App\Services\Reports\Definitions\InventoryTurnoverAbcDefinition;
use App\Services\Reports\Definitions\ProductionCapacityUtilizationDefinition;
use App\Services\Reports\Definitions\ProductionCostAnalysisDefinition;
use App\Services\Reports\Definitions\ProductionDailyProduceDefinition;
use App\Services\Reports\Definitions\ProductionEfficiencyDefinition;
use App\Services\Reports\Definitions\ProductionEmployeeThroughputDefinition;
use App\Services\Reports\Definitions\ProductionForecastVsActualDefinition;
use App\Services\Reports\Definitions\ProductionMaterialRequestVarianceDefinition;
use App\Services\Reports\Definitions\ProductionQualityDefinition;
use App\Services\Reports\Definitions\ProductionRecipeCostingDefinition;
use App\Services\Reports\Definitions\ProductionSchedulingComplianceDefinition;
use App\Services\Reports\Definitions\ProductionShiftHandoverDefinition;
use App\Services\Reports\Definitions\ProductionWasteAnalysisDefinition;
use App\Services\Reports\Definitions\SalesDiscountAnalysisDefinition;
use App\Services\Reports\Definitions\SalesOrderTypeSegmentDefinition;
use App\Services\Reports\Definitions\SalesPerformanceDefinition;
use App\Services\Reports\Definitions\SalesProductMixDefinition;
use App\Services\Reports\Definitions\SalesRefundCancellationDefinition;
use App\Services\Reports\Definitions\SalesStaffProductivityDefinition;
use App\Services\Reports\Definitions\SalesTablePerformanceDefinition;
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
                'definition' => ProductionRecipeCostingDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => ProductionCapacityUtilizationDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => ProductionMaterialRequestVarianceDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => ProductionSchedulingComplianceDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => ProductionForecastVsActualDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => ProductionShiftHandoverDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => ProductionEmployeeThroughputDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => SalesPerformanceDefinition::class,
                'service' => SalesPerformanceReportService::class,
            ],
            [
                'definition' => SalesDiscountAnalysisDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => SalesProductMixDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => SalesStaffProductivityDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => SalesOrderTypeSegmentDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => SalesTablePerformanceDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => SalesRefundCancellationDefinition::class,
                'service' => GenericDefinitionReportService::class,
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
                'definition' => AccountingCashFlowDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => AccountingArAgingDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => AccountingApAgingDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => AccountingBudgetVsActualDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => AccountingCashPositionDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => AccountingFixedAssetRegisterDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => AccountingSupplierInvoiceReconciliationDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => AccountingExpenseClaimsDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => AccountingBankStatementExceptionDefinition::class,
                'service' => GenericDefinitionReportService::class,
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
                'definition' => HRAttendancePunctualityDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => HRPayrollCompensationDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => HRTurnoverRetentionDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => HRAppraisalRatingsDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => HRLeaveBalanceAccrualDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => HRDepartmentStaffingWorkloadDefinition::class,
                'service' => GenericDefinitionReportService::class,
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
            [
                'definition' => InventoryStockVarianceDiscrepancyDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => InventorySupplierPerformanceDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => InventoryExpiryDeadStockDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => InventoryTurnoverAbcDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => InventorySeasonalDemandDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => InventoryReserveAllocationDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
            [
                'definition' => InventoryInterBranchTransferDefinition::class,
                'service' => GenericDefinitionReportService::class,
            ],
        ];
    }

    /**
     * Reports available to a given user (permission-aware).
     */
    public static function availableForUser(?User $user = null): array
    {
        $user = $user ?? auth()->user();
        $allowedCategories = self::userAllowedCategories($user);
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

                // Enforce category-level access (null = all allowed, [] = none allowed)
                if ($allowedCategories !== null) {
                    if (empty($allowedCategories)) {
                        continue;
                    }
                    if (! in_array($meta['category'] ?? '', $allowedCategories, true)) {
                        continue;
                    }
                }

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

    /**
     * Returns the set of report categories this user may access,
     * or null if they may access all categories.
     */
    public static function userAllowedCategories(?User $user): ?array
    {
        if (! $user) {
            return [];
        }

        // Super admins and admins see everything
        if (is_super_admin() || SidebarVisibilityService::isAdmin($user)) {
            return null;
        }

        // HR role sees everything
        if ($user->hasRole('HR')) {
            return null;
        }

        // Manager roles are scoped to their own department category. A role may
        // map to one or more categories (e.g. Operations Manager oversees both
        // production and sales).
        $managerCategoryMap = [
            'Production Manager'  => ['production'],
            'Sales Manager'       => ['sales'],
            'Inventory Manager'   => ['inventory'],
            'Accounting Manager'  => ['accounting'],
            'Operations Manager'  => ['production', 'sales'],
        ];

        $allowed = [];
        foreach ($managerCategoryMap as $role => $categories) {
            if ($user->hasRole($role)) {
                $allowed = array_merge($allowed, $categories);
            }
        }

        // Return matched categories, or empty array (no access) if no manager role matched
        return array_values(array_unique($allowed));
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

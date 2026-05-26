<?php

namespace App\Services;

use App\Models\UserActivityLog;

class ActivityDescriptionService
{
    private static array $exactMap = [
        'saveSale'                => 'Recorded a sale',
        'deleteSale'              => 'Deleted a sale',
        'saveExpense'             => 'Recorded an expense',
        'deleteExpense'           => 'Deleted an expense',
        'savePurchase'            => 'Recorded a purchase',
        'deletePurchase'          => 'Deleted a purchase',
        'approveRequest'          => 'Approved a request',
        'rejectRequest'           => 'Rejected a request',
        'approvePurchase'         => 'Approved a purchase',
        'rejectPurchase'          => 'Rejected a purchase',
        'bulkDelete'              => 'Deleted multiple records',
        'exportData'              => 'Exported data',
        'processPayroll'          => 'Processed payroll',
        'clockIn'                 => 'Clocked in',
        'clockOut'                => 'Clocked out',
        'openStock'               => 'Opened stock for the shift',
        'closeShift'              => 'Closed the shift',
        'dispatchProduction'      => 'Dispatched a production order',
        'createProductionRequest' => 'Created a production request',
        'submitMaterialRequest'   => 'Submitted a material request',
        'resolveVariance'         => 'Resolved a stock variance',
        'voidSale'                => 'Voided a sale',
        'postTransaction'         => 'Posted a transaction',
        'save'                    => 'Saved a record',
        'delete'                  => 'Deleted a record',
        'create'                  => 'Created a record',
        'update'                  => 'Updated a record',
        'approve'                 => 'Approved something',
        'reject'                  => 'Rejected something',
        'submit'                  => 'Submitted a form',
    ];

    private static array $prefixMap = [
        'bulkDelete'  => 'Deleted multiple records',
        'bulkApprove' => 'Approved multiple records',
        'bulkReject'  => 'Rejected multiple records',
        'bulkVoid'    => 'Voided multiple records',
        'export'      => 'Exported data',
        'process'     => 'Processed something',
        'approve'     => 'Approved something',
        'reject'      => 'Rejected something',
        'create'      => 'Created something',
        'delete'      => 'Deleted a record',
        'remove'      => 'Removed a record',
        'save'        => 'Saved a record',
        'update'      => 'Updated a record',
        'submit'      => 'Submitted a form',
        'import'      => 'Imported data',
        'dispatch'    => 'Dispatched something',
        'open'        => 'Opened something',
        'close'       => 'Closed something',
        'clock'       => 'Recorded attendance',
        'generate'    => 'Generated a report',
        'post'        => 'Posted a transaction',
        'void'        => 'Voided a record',
        'resolve'     => 'Resolved something',
        'transfer'    => 'Transferred something',
    ];

    private static array $moduleLabels = [
        'sales'        => 'Sales',
        'inventory'    => 'Inventory',
        'accounting'   => 'Accounting',
        'hr'           => 'HR',
        'production'   => 'Production',
        'analytics'    => 'Analytics',
        'organization' => 'Organization',
        'payroll'      => 'Payroll',
        'audit'        => 'Audit',
        'dashboard'    => 'Dashboard',
        'employees'    => 'Employees',
        'pos'          => 'Point of Sale',
        'reports'      => 'Reports',
        'settings'     => 'Settings',
        'purchasing'   => 'Purchasing',
    ];

    public static function moduleLabel(?string $module): string
    {
        if (!$module) {
            return 'System';
        }

        return self::$moduleLabels[strtolower($module)]
            ?? ucfirst(str_replace(['-', '_'], ' ', $module));
    }

    public static function describe(UserActivityLog $log): string
    {
        $module = self::moduleLabel($log->module);

        return match ($log->event_type) {
            'login'        => 'Logged in',
            'logout'       => 'Logged out',
            'failed_login' => 'Incorrect password attempt',
            'page_view'    => "Visited the {$module} section",
            default        => self::describeAction($log->action_name, $module),
        };
    }

    public static function sentence(UserActivityLog $log): string
    {
        $name   = $log->user->getAttribute('name') ?? 'Someone';
        $ago    = $log->created_at->diffForHumans();
        $module = self::moduleLabel($log->module);

        return match ($log->event_type) {
            'login'        => "{$name} logged in · {$ago}",
            'logout'       => "{$name} logged out · {$ago}",
            'failed_login' => "Incorrect password attempt for {$name} · {$ago}",
            'page_view'    => "{$name} visited {$module} · {$ago}",
            default        => "{$name} " . self::actionVerb($log->action_name) . " in {$module} · {$ago}",
        };
    }

    private static function describeAction(?string $action, string $module): string
    {
        if (!$action) {
            return "Performed an action in {$module}";
        }

        if (isset(self::$exactMap[$action])) {
            return self::$exactMap[$action];
        }

        foreach (self::sortedPrefixMap() as $prefix => $label) {
            if (str_starts_with(strtolower($action), strtolower($prefix))) {
                return $label;
            }
        }

        return "Performed an action in {$module}";
    }

    private static function actionVerb(?string $action): string
    {
        if (!$action) {
            return 'did something';
        }

        if (isset(self::$exactMap[$action])) {
            return lcfirst(self::$exactMap[$action]);
        }

        foreach (self::sortedPrefixMap() as $prefix => $label) {
            if (str_starts_with(strtolower($action), strtolower($prefix))) {
                return lcfirst($label);
            }
        }

        return 'did something';
    }

    /** @return array<string,string> */
    private static function sortedPrefixMap(): array
    {
        $map = self::$prefixMap;
        uksort($map, fn ($a, $b) => strlen($b) - strlen($a));
        return $map;
    }
}

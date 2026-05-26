<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class UserActivityService
{
    // ── Module detection maps ────────────────────────────────────────────────

    protected static array $urlSegmentMap = [
        'inventory'        => 'inventory',
        'stock'            => 'inventory',
        'purchase'         => 'inventory',
        'material'         => 'inventory',
        'batch'            => 'inventory',
        'production'       => 'production',
        'recipe'           => 'production',
        'wip'              => 'production',
        'production-request' => 'production',
        'sales'            => 'sales',
        'pos'              => 'sales',
        'shift'            => 'sales',
        'variance'         => 'sales',
        'cash-register'    => 'sales',
        'stock-opening'    => 'sales',
        'accounting'       => 'accounting',
        'gl'               => 'accounting',
        'trial-balance'    => 'accounting',
        'budget'           => 'accounting',
        'payable'          => 'accounting',
        'employee'         => 'hr',
        'payroll'          => 'hr',
        'leave'            => 'hr',
        'appraisal'        => 'hr',
        'clock-in'         => 'hr',
        'hr'               => 'hr',
        'department'       => 'organization',
        'organization'     => 'organization',
        'uom'              => 'organization',
        'analytics'        => 'analytics',
        'md-reports'       => 'analytics',
        'reporting'        => 'analytics',
        'audit'            => 'audit',
        'user-activity'    => 'audit',
        'roles'            => 'admin',
        'role'             => 'admin',
        'branches'         => 'admin',
        'settings'         => 'admin',
        'permissions'      => 'admin',
        'dashboards'       => 'dashboard',
        'dashboard'        => 'dashboard',
    ];

    protected static array $classSegmentMap = [
        'Inventory'           => 'inventory',
        'Production'          => 'production',
        'SalesDashboard'      => 'sales',
        'Accounting'          => 'accounting',
        'EmployeeModule'      => 'hr',
        'Payroll'             => 'hr',
        'HR'                  => 'hr',
        'Hr'                  => 'hr',
        'Organization'        => 'organization',
        'DepartmentModule'    => 'organization',
        'Analytics'           => 'analytics',
        'AuditManagement'     => 'audit',
        'UserActivityMonitor' => 'audit',
        'Roles'               => 'admin',
        'Branches'            => 'admin',
        'Settings'            => 'admin',
        'Dashboards'          => 'dashboard',
    ];

    // ── Public API ───────────────────────────────────────────────────────────

    public static function trackAuth(Model $user, string $eventType, array $metadata = []): void
    {
        if ($user->getAttribute('is_developer')) {
            return;
        }

        try {
            UserActivityLog::create([
                'user_id'    => $user->getKey(),
                'branch_id'  => $user->getAttribute('branch_id'),
                'session_id' => session()->getId(),
                'event_type' => $eventType,
                'module'     => null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata'   => $metadata ?: null,
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Monitoring must never break auth flow
        }
    }

    public static function trackPageView(Model $user, Request $request): void
    {
        if ($user->getAttribute('is_developer')) {
            return;
        }

        try {
            $url       = $request->url();
            $routeName = $request->route()?->getName() ?? '';
            $module    = self::resolveModuleFromUrl($url, $routeName);

            UserActivityLog::create([
                'user_id'    => $user->getKey(),
                'branch_id'  => $user->getAttribute('branch_id') ?? session('selected_branch_id'),
                'session_id' => session()->getId(),
                'event_type' => 'page_view',
                'module'     => $module,
                'route_name' => $routeName,
                'url'        => $url,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Monitoring must never break page rendering
        }
    }

    public static function trackComponentAction(
        Model $user,
        string $componentClass,
        string $method,
        array $params = []
    ): void {
        if ($user->getAttribute('is_developer')) {
            return;
        }

        try {
            $module = self::resolveModuleFromClass($componentClass);

            UserActivityLog::create([
                'user_id'         => $user->getKey(),
                'branch_id'       => $user->getAttribute('branch_id') ?? session('selected_branch_id'),
                'session_id'      => session()->getId(),
                'event_type'      => 'component_action',
                'module'          => $module,
                'component_class' => $componentClass,
                'action_name'     => $method,
                'ip_address'      => request()->ip(),
                'user_agent'      => request()->userAgent(),
                'metadata'        => $params ? ['params' => $params] : null,
                'created_at'      => now(),
            ]);
        } catch (\Throwable) {
            // Monitoring must never break component actions
        }
    }

    // ── Module resolution ────────────────────────────────────────────────────

    protected static function resolveModuleFromUrl(string $url, string $routeName): ?string
    {
        // Check route name segments first (more reliable)
        foreach (array_keys(self::$urlSegmentMap) as $segment) {
            if (str_contains($routeName, $segment)) {
                return self::$urlSegmentMap[$segment];
            }
        }

        // Fall back to URL path segment matching
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $segments = array_filter(explode('/', $path));

        foreach ($segments as $segment) {
            $segment = strtolower(trim($segment));
            if (isset(self::$urlSegmentMap[$segment])) {
                return self::$urlSegmentMap[$segment];
            }
        }

        return null;
    }

    protected static function resolveModuleFromClass(string $componentClass): ?string
    {
        $parts = explode('\\', $componentClass);

        foreach ($parts as $part) {
            if (isset(self::$classSegmentMap[$part])) {
                return self::$classSegmentMap[$part];
            }
        }

        return null;
    }
}

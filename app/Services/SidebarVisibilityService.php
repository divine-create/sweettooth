<?php

namespace App\Services;

use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Collection;

/**
 * Sidebar Visibility Service (Simplified)
 *
 * Controls sidebar menu visibility based on:
 * 1. User's DEPARTMENT (determines what features they see)
 * 2. User's ROLE LEVEL (determines what they can do)
 *
 * Role Levels:
 * 5 = Super Admin (everything)
 * 4 = Admin (all departments in branch)
 * 3 = Manager (all departments in category)
 * 2 = Supervisor (own department + reports)
 * 1 = Staff (own department, basic access)
 */
class SidebarVisibilityService
{
    // Role levels
    public const LEVEL_SUPER_ADMIN = 5;
    public const LEVEL_ADMIN = 4;
    public const LEVEL_MANAGER = 3;
    public const LEVEL_SUPERVISOR = 2;
    public const LEVEL_STAFF = 1;

    /**
     * Get user's role level (1-5)
     */
    public static function getRoleLevel(?User $user = null): int
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return 0;
        }

        // Legacy fast-path: user_type admin is treated as Super Admin
        if (isset($user->user_type) && $user->user_type === 'admin') {
            return self::LEVEL_SUPER_ADMIN;
        }

        // Check by role level column first (new system)
        $role = $user->roles()->orderByDesc('level')->first();

        if ($role && isset($role->level) && $role->level > 0) {
            return (int) $role->level;
        }

        // Fallback: check by role name (old system compatibility + common variants)
        $superAdminRoles = [
            'Super Admin',
            'super-admin',
            'super_admin',
            'SuperAdmin',
            'Managing Director',
            'admin',
            'Admin',
        ];
        if ($user->hasAnyRole($superAdminRoles)) return self::LEVEL_SUPER_ADMIN;

        $adminRoles = ['Admin', 'admin'];
        if ($user->hasAnyRole($adminRoles)) return self::LEVEL_ADMIN;

        $managerRoles = [
            'Managing Director',
            'Admin',
            'Accounting Manager',
            'Production Manager',
            'Sales Manager',
            'HR Manager',
            'Inventory Manager',
            'Head Chef',
            'Gelato Chef',
            'Floor Manager',
        ];
        if ($user->hasAnyRole($managerRoles)) return self::LEVEL_MANAGER;

        $supervisorRoles = [
            'HR Officer',
            'Accountant',
            'Cost Accountant',
            'Till Supervisor',
            'Cornerstore Supervisor',
            'Consession Supervisor',
            'Coffee Barista Trainer',
            'Lobby Host Supervisor',
            'Kitchen Assistant Supervisor',
            'Hot Kitchen Chef',
            'Pastry Chef',
            'Assistant Shop Floor Manager',
            'Inventory Team Lead',
            'Procurement Officer',
            'Facility Officer',
            'Cleaners Supervisor',
            'Chief Security Officer',
            'Social Media Manager',
        ];
        if ($user->hasAnyRole($supervisorRoles)) return self::LEVEL_SUPERVISOR;

        return self::LEVEL_STAFF;
    }

    /**
     * Check if user is Super Admin (level 5)
     */
    public static function isSuperAdmin(?User $user = null): bool
    {
        return self::getRoleLevel($user) >= self::LEVEL_SUPER_ADMIN;
    }

    /**
     * Check if user is Admin or higher (level 4+)
     */
    public static function isAdmin(?User $user = null): bool
    {
        return self::getRoleLevel($user) >= self::LEVEL_ADMIN;
    }

    /**
     * Check if user is Manager or higher (level 3+)
     */
    public static function isManager(?User $user = null): bool
    {
        return self::getRoleLevel($user) >= self::LEVEL_MANAGER;
    }

    /**
     * Check if user is Supervisor or higher (level 2+)
     */
    public static function isSupervisor(?User $user = null): bool
    {
        return self::getRoleLevel($user) >= self::LEVEL_SUPERVISOR;
    }

    /**
     * Get user's department category name
     */
    public static function getDepartmentCategory(?User $user = null): ?string
    {
        $user = $user ?? auth()->user();
        $category = $user?->department?->category?->name;

        // Fallback mapping when department exists but category was not set (common after recreating departments)
        if (!$category && $user?->department) {
            $deptName = strtolower($user->department->name ?? '');

            // Support-like departments
            if (str_contains($deptName, 'hr') ||
                str_contains($deptName, 'human resource') ||
                str_contains($deptName, 'inventory') ||
                str_contains($deptName, 'warehouse') ||
                str_contains($deptName, 'account')) {
                return 'Support';
            }

            // Production
            if (str_contains($deptName, 'production') || str_contains($deptName, 'factory')) {
                return 'Production';
            }

            // Sales
            if (str_contains($deptName, 'sales') || str_contains($deptName, 'marketing')) {
                return 'Sales';
            }
        }

        return $category;
    }

    /**
     * Get departments user can access in sidebar
     */
    public static function getAccessibleDepartments(?User $user = null): Collection
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return collect();
        }

        $level = self::getRoleLevel($user);
        $branchId = session('current_branch_id') ?? $user->branch_id;

        // Level 5: Super Admin sees all departments (across all branches if needed)
        if ($level >= self::LEVEL_SUPER_ADMIN) {
            $query = Department::where('is_active', true);
            if ($branchId) {
                $query->where(function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                        ->orWhereNull('branch_id');
                });
            }
            return $query->with('category')->orderBy('name')->get();
        }

        // Level 4: Admin sees all departments in their branch
        if ($level >= self::LEVEL_ADMIN) {
            return Department::where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })
                ->where('is_active', true)
                ->with('category')
                ->orderBy('name')
                ->get();
        }

        // Level 3: Manager sees all departments in same category
        if ($level >= self::LEVEL_MANAGER) {
            $categoryId = $user->department?->category_id;
            return Department::where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })
                ->where('category_id', $categoryId)
                ->where('is_active', true)
                ->with('category')
                ->orderBy('name')
                ->get();
        }

        // Level 1-2: Supervisor/Staff see only their department
        $dept = $user->department;
        return $dept ? collect([$dept->load('category')]) : collect();
    }

    /**
     * Get visible menu sections based on department and role level
     */
    public static function getVisibleSections(?User $user = null): array
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return [];
        }

        $level = self::getRoleLevel($user);

        return [
            // Dashboard - everyone
            'dashboard' => true,

            // Department-specific sections (permissions + category)
            'production' => self::canSeeProduction($user) || $level >= self::LEVEL_ADMIN,
            'sales' => self::canSeeSalesManagement($user) || $level >= self::LEVEL_ADMIN,
            'inventory' => self::canSeeInventory($user) || $level >= self::LEVEL_ADMIN,
            'hr' => self::canSeeEmployeeManagement($user) || $level >= self::LEVEL_ADMIN,
            'accounting' => self::canSeeAccounting($user) || $level >= self::LEVEL_ADMIN,

            // Role-level sections
            'reports' => self::canSeeReporting($user),
            'analytics' => self::canSeeAnalytics($user),
            'staff_schedule' => self::hasAnyPermission($user, ['manage-staff-schedule']) || $level >= self::LEVEL_SUPERVISOR,

            // Admin sections (level 4+)
            'organization' => self::canSeeOrganization($user),
            'administration' => $level >= self::LEVEL_ADMIN,
            'user_management' => $level >= self::LEVEL_ADMIN,
            'department_management' => $level >= self::LEVEL_ADMIN,

            // Super Admin only (level 5)
            'roles_permissions' => $level >= self::LEVEL_SUPER_ADMIN,
            'branch_management' => $level >= self::LEVEL_SUPER_ADMIN,
            'system_settings' => $level >= self::LEVEL_SUPER_ADMIN,
            'audit_logs' => $level >= self::LEVEL_SUPER_ADMIN,
        ];
    }

    // =========================================================================
    // LEGACY COMPATIBILITY METHODS (for existing blade templates)
    // These map old method names to new logic
    // =========================================================================

    public static function canSeeAdministration($user = null): bool
    {
        $user = $user ?? auth()->user();
        return self::getRoleLevel($user) >= self::LEVEL_ADMIN
            || self::hasAnyPermission($user, ['manage-organization', 'manage-branches']);
    }

    public static function canSeeOrganization($user = null): bool
    {
        $user = $user ?? auth()->user();
        $level = self::getRoleLevel($user);
        $category = self::getDepartmentCategory($user);

        // Super admins can see organization regardless of department
        if (self::isSuperAdmin($user)) {
            return true;
        }

        // Admins can see organization
        if ($level >= self::LEVEL_ADMIN) {
            return true;
        }

        // Managers can only see organization if they are in HR department
        if ($level >= self::LEVEL_MANAGER && $category === 'HR') {
            return true;
        }

        // Others need specific permissions
        return self::hasAnyPermission($user, ['manage-organization', 'view-employees', 'manage-employees', 'manage-departments']);
    }

    public static function canSeeEmployeeManagement($user = null): bool
    {
        $user = $user ?? auth()->user();
        $level = self::getRoleLevel($user);
        $category = self::getDepartmentCategory($user);

        // Super admins can see employee management regardless of department
        if (self::isSuperAdmin($user)) {
            return true;
        }

        // Admins can see employee management
        if ($level >= self::LEVEL_ADMIN) {
            return true;
        }

        // Managers can only see employee management if they are in HR department
        if ($level >= self::LEVEL_MANAGER && $category === 'HR') {
            return true;
        }

        // Others need specific permissions
        return self::hasAnyPermission($user, ['manage-organization', 'view-employees', 'manage-employees', 'manage-leave', 'view-hr-reports']);
    }

    public static function canSeeDepartments($user = null): bool
    {
        $user = $user ?? auth()->user();
        return self::getRoleLevel($user) >= self::LEVEL_ADMIN
            || self::hasAnyPermission($user, ['manage-departments', 'manage-organization']);
    }

    public static function canSeeLeaveManagement($user = null): bool
    {
        $user = $user ?? auth()->user();
        $level = self::getRoleLevel($user);

        return $level >= self::LEVEL_ADMIN
            || self::hasAnyPermission($user, ['manage-leave', 'view-hr-reports']);
    }

    public static function canSeeAuditManagement($user = null): bool
    {
        $user = $user ?? auth()->user();
        return self::getRoleLevel($user) >= self::LEVEL_SUPER_ADMIN
            || self::hasAnyPermission($user, ['manage-roles', 'manage-branches', 'manage-settings']);
    }

    public static function canSeeInventory($user = null): bool
    {
        $user = $user ?? auth()->user();
        $level = self::getRoleLevel($user);

        // Super admins can see inventory regardless of department
        if (self::isSuperAdmin($user)) {
            return true;
        }

        // Admins can see inventory
        if ($level >= self::LEVEL_ADMIN) {
            return true;
        }

        if (! $user) {
            return false;
        }

        $category = self::getDepartmentCategory($user);
        $departmentName = strtolower((string) ($user->department?->name ?? ''));

        // Users in inventory context can see inventory links.
        if ($category === 'Inventory' || str_contains($departmentName, 'inventory') || str_contains($departmentName, 'store') || str_contains($departmentName, 'warehouse')) {
            return true;
        }

        // Inventory-related roles can see inventory links even if category mapping is incomplete.
        if ($user->hasAnyRole([
            'Inventory Manager',
            'Inventory Team Lead',
            'Inventory Supervisor',
            'Inventory Staff',
            'Stock Controller',
            'Store Keeper',
            'Store Clerk',
        ])) {
            return true;
        }

        return false;
    }

    public static function canSeeInventoryManagement($user = null): bool
    {
        return self::canSeeInventory($user) && (
            self::getRoleLevel($user) >= self::LEVEL_MANAGER
            || self::hasAnyPermission($user, ['manage-inventory'])
        );
    }

    public static function canSeeInventoryCallbacks($user = null): bool
    {
        // Super admins can see inventory callbacks regardless of role level
        if (self::isSuperAdmin($user)) {
            return true;
        }

        return self::canSeeInventory($user) && (
            self::getRoleLevel($user) >= self::LEVEL_MANAGER
            || self::hasAnyPermission($user, ['manage-inventory'])
        );
    }

    public static function canSeeAnalytics($user = null): bool
    {
        $user = $user ?? auth()->user();
        $level = self::getRoleLevel($user);
        $category = self::getDepartmentCategory($user);

        // Super admins can see analytics regardless of department
        if (self::isSuperAdmin($user)) {
            return true;
        }

        // Admins can see analytics
        if ($level >= self::LEVEL_ADMIN) {
            return true;
        }

        // Managers can only see analytics if they are in relevant departments (support, hr, inventory, production, sales)
        if ($level >= self::LEVEL_MANAGER && in_array($category, ['Support', 'HR', 'Inventory', 'Production', 'Sales'])) {
            return true;
        }

        // Others need specific permissions
        return self::hasAnyPermission($user, ['view-analytics']);
    }

    public static function canSeeProduction($user = null): bool
    {
        $user = $user ?? auth()->user();
        $level = self::getRoleLevel($user);
        $category = self::getDepartmentCategory($user);

        // Super admins can see production regardless of department
        if (self::isSuperAdmin($user)) {
            return true;
        }

        // Admins can see production
        if ($level >= self::LEVEL_ADMIN) {
            return true;
        }

        // Managers can only see production if they are in production department
        if ($level >= self::LEVEL_MANAGER && $category === 'Production') {
            return true;
        }

        // Others need specific permissions
        return self::hasAnyPermission($user, ['view-production', 'manage-production', 'view-production-reports']);
    }

    public static function canSeeProductionCallbacks($user = null): bool
    {
        // Super admins can see all production callbacks regardless of department
        if (self::isSuperAdmin($user)) {
            return true;
        }

        return self::canSeeProduction($user) && self::getRoleLevel($user) >= self::LEVEL_MANAGER;
    }

    public static function canSeeSalesManagement($user = null): bool
    {
        $user = $user ?? auth()->user();
        $level = self::getRoleLevel($user);
        $category = self::getDepartmentCategory($user);
        $accountingRoles = ['Accounting Manager', 'Accountant', 'Cost Accountant'];

        // Super admins can see sales management regardless of department
        if (self::isSuperAdmin($user)) {
            return true;
        }

        // Admins can see sales management
        if ($level >= self::LEVEL_ADMIN) {
            return true;
        }

        // Accounting roles should not see sales management
        if ($user && $user->hasAnyRole($accountingRoles)) {
            return false;
        }

        // Managers can only see sales management if they are in sales department
        if ($level >= self::LEVEL_MANAGER && $category === 'Sales') {
            return true;
        }

        // Others need specific permissions
        return self::hasAnyPermission($user, ['view-sales', 'process-sales', 'manage-sales', 'view-sales-reports']);
    }

    public static function canSeeInventoryDashboard($user = null): bool
    {
        return self::canSeeInventory($user);
    }

    public static function canSeeSalesManagerItems($user = null): bool
    {
        return self::canSeeSalesManagement($user) && self::getRoleLevel($user) >= self::LEVEL_MANAGER;
    }

    public static function canSeeReporting($user = null): bool
    {
        $user = $user ?? auth()->user();
        $level = self::getRoleLevel($user);
        $category = self::getDepartmentCategory($user);
        $accountingRoles = ['Accounting Manager', 'Accountant', 'Cost Accountant'];

        // Super admins can see reporting regardless of department
        if (self::isSuperAdmin($user)) {
            return true;
        }

        // Admins can see reporting
        if ($level >= self::LEVEL_ADMIN) {
            return true;
        }

        // Accounting roles should not see reporting
        if ($user && $user->hasAnyRole($accountingRoles)) {
            return false;
        }

        // Managers can only see reporting if they are in relevant departments
        if ($level >= self::LEVEL_MANAGER && in_array($category, ['Support', 'HR', 'Production', 'Sales', 'Accounting'])) {
            return true;
        }

        // Others need specific permissions
        return self::hasAnyPermission($user, ['view-reports', 'export-reports']);
    }

    public static function canSeeAccounting($user = null): bool
    {
        $user = $user ?? auth()->user();
        $level = self::getRoleLevel($user);
        $category = self::getDepartmentCategory($user);

        // Super admins can see accounting regardless of department
        if (self::isSuperAdmin($user)) {
            return true;
        }

        // Admins can see accounting
        if ($level >= self::LEVEL_ADMIN) {
            return true;
        }

        // Managers can only see accounting if they are in accounting department
        if ($level >= self::LEVEL_MANAGER && $category === 'Accounting') {
            return true;
        }

        // Allow Accountant and Accounting Manager roles to see accounting section
        if ($user && $user->hasAnyRole(['Accountant', 'Accounting Manager'])) {
            return true;
        }

        // Others need specific permissions
        return self::hasAnyPermission($user, ['view-accounting', 'manage-accounting', 'view-financial-reports', 'access_accounting', 'view_financial_reports']);
    }

    public static function canSeeRoleAssignments($user = null): bool
    {
        $user = $user ?? auth()->user();
        return self::getRoleLevel($user) >= self::LEVEL_ADMIN
            || self::hasAnyPermission($user, ['manage-organization', 'manage-employees']);
    }

    public static function canSeeRolesPermissions($user = null): bool
    {
        $user = $user ?? auth()->user();
        return self::getRoleLevel($user) >= self::LEVEL_SUPER_ADMIN
            || self::hasAnyPermission($user, ['manage-roles']);
    }

    public static function canSeeBranchManagement($user = null): bool
    {
        $user = $user ?? auth()->user();
        return self::getRoleLevel($user) >= self::LEVEL_SUPER_ADMIN
            || self::hasAnyPermission($user, ['manage-branches']);
    }

    public static function canSeeMDReports($user = null): bool
    {
        $user = $user ?? auth()->user();
        return self::getRoleLevel($user) >= self::LEVEL_ADMIN
            || self::hasAnyPermission($user, ['view-reports', 'export-reports']);
    }

    public static function canSeeSettings($user = null): bool
    {
        $user = $user ?? auth()->user();
        return self::getRoleLevel($user) >= self::LEVEL_SUPER_ADMIN
            || self::hasAnyPermission($user, ['manage-settings']);
    }

    // =========================================================================
    // DEPARTMENT-RESTRICTED ROLE CHECKS (for sidebar department filtering)
    // =========================================================================

    public static function isDepartmentRestrictedProductionRole($user = null): bool
    {
        $user = $user ?? auth()->user();
        $level = self::getRoleLevel($user);
        $category = self::getDepartmentCategory($user);

        // Production category and below Manager level = restricted to own dept
        return $category === 'Production' && $level < self::LEVEL_MANAGER;
    }

    public static function isProductionAdminRole($user = null): bool
    {
        $user = $user ?? auth()->user();
        $level = self::getRoleLevel($user);
        $category = self::getDepartmentCategory($user);

        // Admin+ OR Manager in Production
        return $level >= self::LEVEL_ADMIN || ($category === 'Production' && $level >= self::LEVEL_MANAGER);
    }

    public static function isDepartmentRestrictedSalesRole($user = null): bool
    {
        $user = $user ?? auth()->user();
        $level = self::getRoleLevel($user);
        $category = self::getDepartmentCategory($user);

        // Sales category and below Manager level = restricted to own dept
        return $category === 'Sales' && $level < self::LEVEL_MANAGER;
    }

    public static function isSalesAdminRole($user = null): bool
    {
        $user = $user ?? auth()->user();
        $level = self::getRoleLevel($user);
        $category = self::getDepartmentCategory($user);

        // Admin+ OR Manager in Sales
        return $level >= self::LEVEL_ADMIN || ($category === 'Sales' && $level >= self::LEVEL_MANAGER);
    }

    // =========================================================================
    // PRODUCTION MENU ITEMS
    // =========================================================================

    public static function getProductionDepartments(?User $user = null): Collection
    {
        return self::getAccessibleDepartments($user)
            ->filter(fn($d) => $d->category?->name === 'Production');
    }

    // =========================================================================
    // SALES MENU ITEMS
    // =========================================================================

    public static function getSalesDepartments(?User $user = null): Collection
    {
        return self::getAccessibleDepartments($user)
            ->filter(fn($d) => $d->category?->name === 'Sales');
    }

    /**
     * Safe permission check helper
     */
    private static function hasAnyPermission(?User $user, array $permissions): bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasAnyPermission')) {
            return $user->hasAnyPermission($permissions);
        }

        foreach ($permissions as $permission) {
            if (method_exists($user, 'can') && $user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}

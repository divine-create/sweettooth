<?php

namespace App\Traits;

use App\Helpers\RolePermission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Trait for filtering data based on department access
 * 
 * Used to automatically scope queries based on user role:
 * - Regular staff: Only their department
 * - Accounting Manager: All departments in their branch
 * - HR Manager: All departments in their branch (if needed)
 * - Super Admin/Managing Director: All departments
 */
trait DepartmentAccessTrait
{
    /**
     * Scope to filter by user's department access
     * 
     * For Accounting Managers, returns all data from the branch
     * For regular staff, returns only their department's data
     */
    public function scopeByUserDepartmentAccess(Builder $query, $user = null)
    {
        $user = $user ?? Auth::guard('web')->user();

        if (!$user) {
            return $query->whereRaw('1=0'); // No access
        }

        // Super Admin and Managing Director get all data
        if (RolePermission::isSuperAdmin() || RolePermission::isManagingDirector()) {
            return $query;
        }

        // Accounting Manager gets all production data in their branch
        if (RolePermission::isAccountingManager()) {
            return $query->whereHas('shift', function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id ?? null);
            });
        }

        // HR Manager gets all employees in their branch
        if (RolePermission::isHRManager()) {
            return $query->where('branch_id', $user->branch_id ?? null);
        }

        // Regular staff get only their department
        if ($user->department_id) {
            return $query->where('department_id', $user->department_id);
        }

        // Default: no access
        return $query->whereRaw('1=0');
    }

    /**
     * Scope to filter by user's branch access
     */
    public function scopeByUserBranchAccess(Builder $query, $user = null)
    {
        $user = $user ?? Auth::guard('web')->user();

        if (!$user) {
            return $query->whereRaw('1=0'); // No access
        }

        // Super Admin gets all data
        if (RolePermission::isSuperAdmin()) {
            return $query;
        }

        // Everyone else gets their branch
        return $query->where('branch_id', $user->branch_id ?? null);
    }

    /**
     * Check if current user has cross-department access
     */
    public static function userHasCrossDepartmentAccess(): bool
    {
        return RolePermission::hasCrossDepartmentAccess();
    }

    /**
     * Get the department filter constraint for current user
     * Returns the department_id or null if user has cross-department access
     */
    public static function getCurrentUserDepartmentFilter()
    {
        $user = Auth::guard('web')->user();

        if (!$user) {
            return null;
        }

        // Cross-department access users (Accounting, Super Admin, Managing Director) get null (no filter)
        if (RolePermission::hasCrossDepartmentAccess()) {
            return null;
        }

        // Regular staff get their department
        return $user->department_id ?? null;
    }
}

<?php

namespace App\Services;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Employee;
use App\Models\User;
use App\Models\Branch;
use App\Models\Department;
use App\Services\SidebarVisibilityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

/**
 * Phase 2: Context-Aware Access Control Service
 * 
 * Implements branch-level, department-level, and shift-level permission checks
 * to provide granular, contextual access control across the system.
 */
class ContextualAccessControlService
{
    /**
     * Determine if user has permission in a specific branch context
     */
    public static function userCanInBranch(
        Model $user,
        string $permission,
        Branch $branch
    ): bool {
        // Super admins have access everywhere
        if ($user->hasAnyRole(['Super Admin', 'Managing Director'])) {
            return true;
        }

        // Check if user is assigned to this branch
        if (!self::userBelongsToBranch($user, $branch)) {
            return false;
        }

        // Check if user has the permission
        return $user->can($permission);
    }

    /**
     * Determine if user has permission in a specific department context
     */
    public static function userCanInDepartment(
        Model $user,
        string $permission,
        Department $department
    ): bool {
        // Super admins have access everywhere
        if ($user->hasAnyRole(['Super Admin', 'Managing Director'])) {
            return true;
        }

        // Check if user belongs to this department
        if (!self::userBelongsToDepartment($user, $department)) {
            return false;
        }

        // Managers have extended permissions in their department
        if (SidebarVisibilityService::getRoleLevel($user) >= SidebarVisibilityService::LEVEL_MANAGER) {
            return self::departmentHeadCanPermission($user, $permission, $department);
        }

        // Check if user has the permission
        return $user->can($permission);
    }

    /**
     * Determine if user has permission for a specific shift
     */
    public static function userCanInShift(
        Model $user,
        string $permission,
        $shift
    ): bool {
        // Super admins have access everywhere
        if ($user->hasAnyRole(['Super Admin', 'Managing Director'])) {
            return true;
        }

        // Check if user is assigned to this shift
        if (!self::userBelongsToShift($user, $shift)) {
            return false;
        }

        // Supervisors have extended permissions during their shifts
        if (SidebarVisibilityService::getRoleLevel($user) >= SidebarVisibilityService::LEVEL_SUPERVISOR) {
            return self::supervisorCanPermission($user, $permission, $shift);
        }

        return $user->can($permission);
    }

    /**
     * Check if user belongs to a branch
     */
    public static function userBelongsToBranch(Model $user, Branch $branch): bool
    {
        // Super admins belong to all branches
        if ($user->hasAnyRole(['Super Admin', 'Managing Director'])) {
            return true;
        }

        // Check if user has a direct branch assignment
        if (method_exists($user, 'branches')) {
            return $user->branches()->where('branch_id', $branch->id)->exists();
        }

        return false;
    }

    /**
     * Check if user belongs to a department
     */
    public static function userBelongsToDepartment(Model $user, Department $department): bool
    {
        // Super admins belong to all departments
        if ($user->hasAnyRole(['Super Admin', 'Managing Director'])) {
            return true;
        }

        // Check if user has a direct department assignment
        if (method_exists($user, 'department')) {
            return $user->department_id === $department->id;
        }

        return false;
    }

    /**
     * Check if user belongs to a shift
     */
    public static function userBelongsToShift(Model $user, $shift): bool
    {
        // Super admins belong to all shifts
        if ($user->hasAnyRole(['Super Admin', 'Managing Director'])) {
            return true;
        }

        // Check shift assignment - this depends on your shift model structure
        if (method_exists($shift, 'employees')) {
            return $shift->employees()->where('employee_id', $user->id)->exists();
        }

        if (method_exists($user, 'shifts')) {
            return $user->shifts()->where('shift_id', $shift->id)->exists();
        }

        return false;
    }

    /**
     * Check if department head can perform permission
     */
    private static function departmentHeadCanPermission(
        Model $user,
        string $permission,
        Department $department
    ): bool {
        return $user->can($permission);
    }

    /**
     * Check if supervisor can perform permission during shift
     */
    private static function supervisorCanPermission(
        Model $user,
        string $permission,
        $shift
    ): bool {
        return $user->can($permission);
    }

    /**
     * Get all permissions accessible by user in branch context
     */
    public static function getAccessiblePermissionsInBranch(
        Model $user,
        Branch $branch
    ): array {
        if (!self::userBelongsToBranch($user, $branch)) {
            return [];
        }

        return $user->getAllPermissions()->pluck('name')->toArray();
    }

    /**
     * Get all permissions accessible by user in department context
     */
    public static function getAccessiblePermissionsInDepartment(
        Model $user,
        Department $department
    ): array {
        if (!self::userBelongsToDepartment($user, $department)) {
            return [];
        }

        $basePermissions = $user->getAllPermissions()->pluck('name')->toArray();

        return array_unique($basePermissions);
    }

    /**
     * Log contextual access attempt
     */
    public static function logAccessAttempt(
        Model $user,
        string $permission,
        string $context,
        ?string $contextId,
        bool $granted
    ): void {
        Log::info('Contextual access attempt', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'permission' => $permission,
            'context' => $context,
            'context_id' => $contextId,
            'granted' => $granted,
            'timestamp' => now(),
        ]);
    }

    /**
     * Validate user access to resource in context
     * Returns detailed access info
     */
    public static function validateAccess(
        Model $user,
        string $permission,
        string $contextType,
        $contextModel
    ): array {
        $canAccess = false;
        $reason = '';

        if ($contextType === 'branch') {
            $canAccess = self::userCanInBranch($user, $permission, $contextModel);
            if (!$canAccess) {
                $reason = $user->can($permission)
                    ? 'User not assigned to this branch'
                    : 'User lacks required permission';
            }
        } elseif ($contextType === 'department') {
            $canAccess = self::userCanInDepartment($user, $permission, $contextModel);
            if (!$canAccess) {
                $reason = $user->can($permission)
                    ? 'User not assigned to this department'
                    : 'User lacks required permission';
            }
        } elseif ($contextType === 'shift') {
            $canAccess = self::userCanInShift($user, $permission, $contextModel);
            if (!$canAccess) {
                $reason = $user->can($permission)
                    ? 'User not assigned to this shift'
                    : 'User lacks required permission';
            }
        }

        self::logAccessAttempt($user, $permission, $contextType, $contextModel?->id, $canAccess);

        return [
            'can_access' => $canAccess,
            'permission' => $permission,
            'context_type' => $contextType,
            'context_id' => $contextModel?->id,
            'reason' => $reason,
            'user_id' => $user->id,
            'timestamp' => now(),
        ];
    }
}

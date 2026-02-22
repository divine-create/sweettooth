<?php

use App\Models\Branch;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;

/**
 * Branch Helper Functions
 *
 * These helper functions provide utilities for managing branch context
 * in a multi-branch application with super admin capabilities.
 */
/**
 * Audit Helper - Delegates to AuditService
 *
 * Provides a convenient function-based API for audit logging.
 * For more advanced features, use AuditService directly.
 *
 * @see App\Services\AuditService
 */
if (!function_exists('audit')) {
    function audit(
        $causer,
        $action,
        $auditable = null,
        $description = null,
        $status = 'completed',
        $approvalRequest = null,
        array $metadata = []
    ) {
        return \App\Services\AuditService::log(
            $causer,
            $action,
            $auditable,
            $description,
            $status,
            $approvalRequest,
            $metadata
        );
    }
}

if (!function_exists('current_branch_id')) {
    /**
     * Get the current branch ID based on user context.
     *
     * Returns the branch ID from (in priority order):
     * 1. URL parameter (?b_id=) - if not empty
     * 2. Session (for super admins who can switch branches)
     * 3. Authenticated user's branch_id
     * 4. Authenticated user's department's branch_id
     * 5. First active branch (always fallback)
     *
     * @return string|null
     */
    function current_branch_id(): ?string
    {
        // First check URL parameter (highest priority) - only if non-empty
        if (request()->has('b_id')) {
            $b_id = request()->query('b_id');
            if ($b_id && trim($b_id) !== '') {
                session(['selected_branch_id' => $b_id]);
                return $b_id;
            }
        }

        // Check session
        if (session()->has('selected_branch_id') && session('selected_branch_id')) {
            return session('selected_branch_id');
        }

        // Check authenticated user's context
        if (auth()->check()) {
            $user = auth()->user();
            
            // Try direct branch_id first
            if (isset($user->branch_id) && $user->branch_id) {
                session(['selected_branch_id' => $user->branch_id]);
                return $user->branch_id;
            }
            
            // Try to get branch from user's department
            if (isset($user->department_id) && $user->department_id) {
                $department = \App\Models\Department::find($user->department_id);
                if ($department && $department->branch_id) {
                    session(['selected_branch_id' => $department->branch_id]);
                    return $department->branch_id;
                }
            }
            
            // Try loaded relationship
            if (method_exists($user, 'department') && $user->department && $user->department->branch_id) {
                session(['selected_branch_id' => $user->department->branch_id]);
                return $user->department->branch_id;
            }
        }

        // Final fallback - get and cache first active branch
        $defaultBranch = \App\Models\Branch::where('is_active', 1)->first();
        if ($defaultBranch) {
            session(['selected_branch_id' => $defaultBranch->id]);
            return $defaultBranch->id;
        }

        return null;
    }
}

if (!function_exists('get_user_auth')) {
    /**
     * Get current authenticated user (unified system)
     * @deprecated Use get_current_user() from AuthorizationHelper instead
     */
    function get_user_auth()
    {
        // Use Auth::id() which works even with corrupted user objects
        $userId = \Illuminate\Support\Facades\Auth::id();
        
        if (!$userId) {
            return null;
        }
        
        // Load user directly from database to avoid serialization issues
        $user = \App\Models\User::find($userId);
        
        if ($user && is_object($user)) {
            return $user;
        }
        
        return null;
    }
}

if (!function_exists('current_actor')) {
    /**
     * Get the current authenticated actor (user) in the unified system
     *
     * @return \App\Models\User|null
     */
    function current_actor(): ?\App\Models\User
    {
        // Unified system - all users authenticated via web guard
        $user = \Illuminate\Support\Facades\Auth::user();

        // If no user, return null
        if (!$user) {
            return null;
        }

        // Ensure we're returning a User object, not a serialized string
        if (is_string($user)) {
            // Try to reload user by ID
            $userId = \Illuminate\Support\Facades\Auth::id();
            if ($userId) {
                $user = \App\Models\User::find($userId);
                if ($user && is_object($user)) {
                    \Illuminate\Support\Facades\Auth::setUser($user);
                    return $user;
                }
            }
            \Illuminate\Support\Facades\Auth::logout();
            return null;
        }

        // If not a User instance, reload by ID
        if (!$user instanceof \App\Models\User) {
            $userId = \Illuminate\Support\Facades\Auth::id();
            if ($userId) {
                $user = \App\Models\User::find($userId);
                if ($user && is_object($user)) {
                    \Illuminate\Support\Facades\Auth::setUser($user);
                    return $user;
                }
            }
            \Illuminate\Support\Facades\Auth::logout();
            return null;
        }

        return $user;
    }
}


// is_super_admin function moved to AuthorizationHelper.php for unified system

if (!function_exists('can_access_all_branches')) {
    /**
     * Check if the current user can access all branches.
     *
     * Returns true for super admins who can access all branches.
     * Returns false for branch-specific users and unauthenticated users.
     *
     * @return bool
     */
    function can_access_all_branches(): bool
    {
        // In unified system, only super admins can access all branches
        return is_super_admin();
    }
}

if (!function_exists('get_accessible_branches')) {
    /**
     * Get all branches accessible to the current user.
     *
     * Returns all active branches for super admins, or just the assigned
     * branch for regular employees.
     *
     * @return \Illuminate\Support\Collection
     */
    function get_accessible_branches(): \Illuminate\Support\Collection
    {
        if (can_access_all_branches()) {
            return \App\Models\Branch::where('is_active', 1)
                ->orderBy('name')
                ->get();
        }

        if (auth()->check()) {
            $branchId = auth()->user()->branch_id;
            return \App\Models\Branch::where('id', $branchId)->get();
        }

        return collect([]);
    }
}

if (!function_exists('set_current_branch')) {
    /**
     * Set the current branch context.
     *
     * This updates the session with the selected branch ID and
     * optionally updates the user's last accessed branch.
     *
     * @param int $branchId
     * @param bool $updateUserPreference
     * @return void
     */
    function set_current_branch(string $branchId, bool $updateUserPreference = true): void
    {
        session(['selected_branch_id' => $branchId]);

        if ($updateUserPreference && auth()->check() && is_super_admin()) {
            auth()->user()->update(['last_accessed_branch_id' => $branchId]);
        }
    }
}

if (!function_exists('current_branch')) {
    /**
     * Get the current branch model instance.
     *
     * @return \App\Models\Branch|null
     */
    function current_branch(): ?\App\Models\Branch
    {
        $branchId = current_branch_id();

        if (!$branchId) {
            return null;
        }

        return \App\Models\Branch::find($branchId);
    }
}

if (!function_exists('validate_branch_access')) {
    /**
     * Validate that the current user can access the specified branch.
     *
     * @param int $branchId
     * @return bool
     */
    function validate_branch_access(string $branchId): bool
    {
        // Super admins can access all branches
        if (can_access_all_branches()) {
            return true;
        }

        // Regular employees can only access their assigned branch
        if (auth()->check()) {
            return auth()->user()->branch_id === $branchId;
        }

        return false;
    }
}

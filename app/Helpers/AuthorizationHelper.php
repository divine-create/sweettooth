<?php

use Illuminate\Support\Facades\Auth;
use App\Models\Branch;

/**
 * Authorization Helper Functions
 * 
 * Single source of truth for authentication and authorization checks.
 * All authorization logic should delegate to these functions.
 * 
 * AUTHORIZATION MODEL:
 * - Super Admin: authenticated in 'web' guard AND has Super Admin / Managing Director / Admin role
 * - Employee: authenticated via their own guard with employee roles
 */

if (!function_exists('is_super_admin')) {
    /**
     * Check if current user is a super admin.
     *
     * Super admins are users with any of these roles:
     * - super-admin / Super Admin / super_admin
     * - Managing Director
     * - user_type = 'admin' (legacy system)
     *
     * @return bool
     */
    function is_super_admin(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        
        // Legacy check: if user_type is explicitly admin
        if (isset($user->user_type) && $user->user_type === 'admin') {
            return true;
        }
        
        $allowedRoleNames = [
            'super admin',
            'super-admin',
            'super_admin',
            'superadmin',
            'admin',
            'managing director',
        ];

        // Check if user has super-admin role (include common legacy variants)
        if (method_exists($user, 'hasAnyRole')) {
            if ($user->hasAnyRole([
                'Super Admin',
                'super-admin',
                'super_admin',
                'SuperAdmin',
                'Managing Director',
                'admin',
                'Admin',
            ])) {
                return true;
            }
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('Super Admin') 
                || $user->hasRole('super-admin') 
                || $user->hasRole('super_admin')
                || $user->hasRole('SuperAdmin')
                || $user->hasRole('Managing Director')
                || $user->hasRole('admin')
                || $user->hasRole('Admin');
        }

        if (method_exists($user, 'getRoleNames')) {
            $roleNames = $user->getRoleNames()
                ->map(fn ($name) => strtolower(trim($name)))
                ->toArray();
            foreach ($allowedRoleNames as $allowed) {
                if (in_array($allowed, $roleNames, true)) {
                    return true;
                }
            }
        }

        // Permission-based fallback for super admin capabilities
        if (method_exists($user, 'hasAnyPermission')) {
            if ($user->hasAnyPermission([
                'manage-branches',
                'manage-roles',
                'manage-settings',
                'manage-organization',
            ])) {
                return true;
            }
        }
        
        return false;
    }
}

if (!function_exists('is_admin')) {
    /**
     * Check if current user is an admin (but not super-admin).
     *
     * @return bool
     */
    function is_admin(): bool
    {
        $user = Auth::user();
        if (!$user || is_super_admin()) {
            return false;
        }
        
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('admin') || $user->hasRole('Admin');
        }
        
        return false;
    }
}

if (!function_exists('is_employee')) {
    /**
     * Check if current user is an employee.
     *
     * @return bool
     */
    function is_employee(): bool
    {
        $user = Auth::user();
        if (!$user || is_super_admin()) {
            return false;
        }

        return true;
    }
}

if (!function_exists('has_role')) {
    /**
     * Check if current user has a specific role.
     *
     * @param string $role
     * @return bool
     */
    function has_role(string $role): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole($role);
        }
        
        return false;
    }
}

if (!function_exists('has_permission')) {
    /**
     * Check if current user has a specific permission.
     *
     * @param string $permission
     * @return bool
     */
    function has_permission(string $permission): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        if (!method_exists($user, 'can')) {
            return false;
        }

        if (! $user->can($permission)) {
            return false;
        }

        if (is_super_admin()) {
            return true;
        }

        $dept = request()->attributes->get('current_department');
        if (! $dept) {
            return true;
        }

        return user_can_access_department($user, $dept);
    }
}

if (!function_exists('get_user_role_level')) {
    /**
     * Get user's role level (1-5) using the same logic as DepartmentScopeMiddleware.
     */
    function get_user_role_level($user): int
    {
        if (!$user) {
            return 1;
        }

        if (isset($user->user_type) && $user->user_type === 'admin') {
            return 5;
        }

        if (function_exists('is_super_admin') && is_super_admin()) {
            return 5;
        }

        if (method_exists($user, 'roles')) {
            $role = $user->roles()->orderByDesc('level')->first();
            if ($role && isset($role->level)) {
                return (int) $role->level;
            }
        }

        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole('Super Admin')) return 5;
            if ($user->hasRole('Admin')) return 4;

            $managerRoles = [
                'Managing Director', 'Admin', 'Accounting Manager',
                'Production Manager', 'Sales Manager', 'HR', 'HR Manager',
                'Inventory Manager', 'Head Chef', 'Gelato Chef', 'Floor Manager',
            ];
            if ($user->hasAnyRole($managerRoles)) return 3;

            $supervisorRoles = [
                'HR Officer', 'Accountant', 'Cost Accountant', 'Till Supervisor', 'Cornerstore Supervisor',
                'Consession Supervisor', 'Coffee Barista Trainer', 'Lobby Host Supervisor', 'Kitchen Assistant Supervisor',
                'Hot Kitchen Chef', 'Pastry Chef', 'Assistant Shop Floor Manager', 'Inventory Team Lead',
                'Procurement Officer', 'Facility Officer', 'Cleaners Supervisor', 'Chief Security Officer',
                'Social Media Manager',
            ];
            if ($user->hasAnyRole($supervisorRoles)) return 2;
        }

        return 1;
    }
}

if (!function_exists('user_can_access_department')) {
    /**
     * Check if a user can access a specific department context.
     */
    function user_can_access_department($user, $department): bool
    {
        if (!$user) {
            return false;
        }

        if (is_super_admin()) {
            return true;
        }

        $dept = $department;
        if (is_string($department)) {
            $dept = \App\Models\Department::where('slug', $department)->first();
        }

        if (! $dept) {
            return false;
        }

        $roleLevel = get_user_role_level($user);

        if ($roleLevel >= 4) {
            return isset($user->branch_id) && $user->branch_id === $dept->branch_id;
        }

        if ($roleLevel >= 3) {
            $userDept = $user->department ?? \App\Models\Department::find($user->department_id);
            if (! $userDept) {
                return false;
            }

            return $userDept->category_id === $dept->category_id
                && $user->branch_id === $dept->branch_id;
        }

        $userDept = $user->department ?? \App\Models\Department::find($user->department_id);
        if (! $userDept) {
            return false;
        }

        return $userDept->slug === $dept->slug;
    }
}

if (!function_exists('get_current_user')) {
    /**
     * Get the currently authenticated user.
     *
     * Ensures we're returning a User object, not a serialized string.
     * If user got serialized as string, force re-authentication.
     *
     * @return \App\Models\User|null
     */
    function get_current_user()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        
        // Ensure we're returning a User object, not a serialized string
        // This handles cases where session/cache may have serialized the user
        if ($user && is_string($user)) {
            \Log::warning('Auth user was serialized as string, attempting recovery', [
                'user_string' => substr($user, 0, 50),
            ]);
            
            // Try to reload user by ID
            $userId = \Illuminate\Support\Facades\Auth::id();
            if ($userId) {
                $user = \App\Models\User::find($userId);
                if ($user) {
                    // Re-authenticate with fresh user data
                    \Illuminate\Support\Facades\Auth::setUser($user);
                    return $user;
                }
            }
            
           \Log::error('Failed to recover serialized user', ['value' => $user]);
           \Illuminate\Support\Facades\Auth::logout();
           return null;
       }
       
       if ($user && !is_object($user)) {
           \Log::warning('Auth user is not an object', [
               'user_type' => gettype($user),
           ]);
           \Illuminate\Support\Facades\Auth::logout();
           return null;
       }
       
       return $user;
    }
}

if (!function_exists('get_user_branch_id')) {
    /**
     * Get the branch ID for the current user.
     *
     * - Super admins: returns session-selected branch (allows switching)
     * - Employees: returns their assigned branch or department's branch
     * - Unauthenticated: returns null
     *
     * @return string|null
     */
    function get_user_branch_id(): ?string
    {
        $user = get_current_user();
        
        if (!$user) {
            return null;
        }
        
        // Super admins can switch branches via session
        if (is_super_admin()) {
            if (session()->has('selected_branch_id')) {
                return session('selected_branch_id');
            }
            // Fallback to first active branch if no session
            $defaultBranch = Branch::where('is_active', 1)->first();
            return $defaultBranch?->id;
        }

        // Regular users: try multiple sources for branch
        
        // 1. Direct branch_id on user
        if (isset($user->branch_id) && $user->branch_id) {
            return $user->branch_id;
        }

        // 2. Get branch from department_id
        if (isset($user->department_id) && $user->department_id) {
            $department = \App\Models\Department::where('id', $user->department_id)->first();
            if ($department && $department->branch_id) {
                return $department->branch_id;
            }
        }

        // 3. Try loaded department relationship
        if (method_exists($user, 'department') && $user->department) {
            if ($user->department->branch_id) {
                return $user->department->branch_id;
            }
        }

        // 4. Last resort: get first active branch
        $defaultBranch = Branch::where('is_active', 1)->first();
        return $defaultBranch?->id;
    }
}



if (!function_exists('validate_branch_access')) {
    /**
     * Validate that the current user can access the specified branch.
     *
     * - Super admins can access any active branch
     * - Employees can only access their assigned branch
     * - Unauthenticated users cannot access any branch
     *
     * @param string $branchId
     * @return bool
     */
    function validate_branch_access(string $branchId): bool
    {
        $user = get_current_user();
        
        if (!$user) {
            return false;
        }
        
        // Super admins can access all branches
        if (is_super_admin()) {
            return Branch::where('id', $branchId)->where('is_active', 1)->exists();
        }

        // Regular users can only access their assigned branch
        if (isset($user->branch_id)) {
            return $user->branch_id === $branchId;
        }

        return false;
    }
}

if (!function_exists('can_access_all_branches')) {
    /**
     * Check if current user can access all branches.
     *
     * @return bool
     */
    function can_access_all_branches(): bool
    {
        return is_super_admin();
    }
}

if (!function_exists('get_accessible_branches')) {
    /**
     * Get all branches accessible to the current user.
     *
     * - Super admins: all active branches
     * - Employees: only their assigned branch
     * - Unauthenticated: empty collection
     *
     * @return \Illuminate\Support\Collection
     */
    function get_accessible_branches()
    {
        $user = get_current_user();
        
        if (!$user) {
            return collect([]);
        }
        
        if (can_access_all_branches()) {
            return Branch::where('is_active', 1)
                ->orderBy('name')
                ->get();
        }

        if (isset($user->branch_id)) {
            return Branch::where('id', $user->branch_id)->get();
        }

        return collect([]);
    }
}

if (!function_exists('set_current_branch')) {
    /**
     * Set the current branch context in session.
     *
     * Only super admins can change their branch context.
     *
     * @param string $branchId
     * @param bool $updateUserPreference
     * @return void
     */
    function set_current_branch(string $branchId, bool $updateUserPreference = true): void
    {
        if (!is_super_admin()) {
            return;
        }

        session(['selected_branch_id' => $branchId]);

        // Only try to update user preference if explicitly requested
        if ($updateUserPreference) {
            try {
                // Use Auth::user() directly and ensure it's a valid User object
                $user = \Illuminate\Support\Facades\Auth::user();
                
                // Safety check: ensure user is a User object and has the update method
                if ($user && $user instanceof \App\Models\User && is_object($user)) {
                    $user->update(['last_accessed_branch_id' => $branchId]);
                } else if (is_string($user)) {
                    // If serialized, try to reload
                    $userId = $user;
                    if (strlen($user) === 36 && substr_count($user, '-') === 4) {
                        $loadedUser = \App\Models\User::find($user);
                        if ($loadedUser) {
                            $loadedUser->update(['last_accessed_branch_id' => $branchId]);
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to update user branch preference', [
                    'user_type' => gettype($user) ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

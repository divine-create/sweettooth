<?php

use App\Models\Branch;
use App\Models\Department;
use App\Models\GlobalBusinessConfiguration;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

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
 * @see AuditService
 */
if (! function_exists('audit')) {
    function audit(
        $causer,
        $action,
        $auditable = null,
        $description = null,
        $status = 'completed',
        $approvalRequest = null,
        array $metadata = []
    ) {
        return AuditService::log(
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

if (! function_exists('opening_stock_entry_enabled')) {
    /**
     * Whether the temporary go-live opening-stock entry tool is enabled.
     *
     * Backed by the single global_business_configurations row. Defaults to true
     * if the row/column is missing so a fresh install can run go-live entry.
     * Cached per request (the lock action triggers a fresh request afterwards).
     */
    function opening_stock_entry_enabled(): bool
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        try {
            $config = GlobalBusinessConfiguration::first();
            $cached = $config ? (bool) $config->opening_stock_entry_enabled : true;
        } catch (Throwable $e) {
            $cached = true;
        }

        return $cached;
    }
}

if (! function_exists('current_branch_id')) {
    /**
     * Get the current branch ID based on user context.
     *
     * Returns the branch ID from (in priority order):
     * 1. URL parameter (?b_id=) - if not empty
     * 2. Session (for super admins who can switch branches)
     * 3. Authenticated user's branch_id
     * 4. Authenticated user's department's branch_id
     * 5. First active branch (always fallback)
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
                $department = Department::find($user->department_id);
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
        $defaultBranch = Branch::where('is_active', 1)->first();
        if ($defaultBranch) {
            session(['selected_branch_id' => $defaultBranch->id]);

            return $defaultBranch->id;
        }

        return null;
    }
}

if (! function_exists('get_user_auth')) {
    /**
     * Get current authenticated user (unified system)
     *
     * @deprecated Use get_current_user() from AuthorizationHelper instead
     */
    function get_user_auth()
    {
        // Use Auth::id() which works even with corrupted user objects
        $userId = Auth::id();

        if (! $userId) {
            return null;
        }

        // Load user directly from database to avoid serialization issues
        $user = User::find($userId);

        if ($user && is_object($user)) {
            return $user;
        }

        return null;
    }
}

if (! function_exists('current_actor')) {
    /**
     * Get the current authenticated actor (user) in the unified system
     */
    function current_actor(): ?User
    {
        // Unified system - all users authenticated via web guard
        $user = Auth::user();

        // If no user, return null
        if (! $user) {
            return null;
        }

        // Ensure we're returning a User object, not a serialized string
        if (is_string($user)) {
            // Try to reload user by ID
            $userId = Auth::id();
            if ($userId) {
                $user = User::find($userId);
                if ($user && is_object($user)) {
                    Auth::setUser($user);

                    return $user;
                }
            }
            Auth::logout();

            return null;
        }

        // If not a User instance, reload by ID
        if (! $user instanceof User) {
            $userId = Auth::id();
            if ($userId) {
                $user = User::find($userId);
                if ($user && is_object($user)) {
                    Auth::setUser($user);

                    return $user;
                }
            }
            Auth::logout();

            return null;
        }

        return $user;
    }
}

// is_super_admin function moved to AuthorizationHelper.php for unified system

if (! function_exists('can_access_all_branches')) {
    /**
     * Check if the current user can access all branches.
     *
     * Returns true for super admins who can access all branches.
     * Returns false for branch-specific users and unauthenticated users.
     */
    function can_access_all_branches(): bool
    {
        // In unified system, only super admins can access all branches
        return is_super_admin();
    }
}

if (! function_exists('get_accessible_branches')) {
    /**
     * Get all branches accessible to the current user.
     *
     * Returns all active branches for super admins, or just the assigned
     * branch for regular employees.
     */
    function get_accessible_branches(): Collection
    {
        if (can_access_all_branches()) {
            return Branch::where('is_active', 1)
                ->orderBy('name')
                ->get();
        }

        if (auth()->check()) {
            $branchId = auth()->user()->branch_id;

            return Branch::where('id', $branchId)->get();
        }

        return collect([]);
    }
}

if (! function_exists('set_current_branch')) {
    /**
     * Set the current branch context.
     *
     * This updates the session with the selected branch ID and
     * optionally updates the user's last accessed branch.
     *
     * @param  int  $branchId
     */
    function set_current_branch(string $branchId, bool $updateUserPreference = true): void
    {
        session(['selected_branch_id' => $branchId]);

        if ($updateUserPreference && auth()->check() && is_super_admin()) {
            auth()->user()->update(['last_accessed_branch_id' => $branchId]);
        }
    }
}

if (! function_exists('current_branch')) {
    /**
     * Get the current branch model instance.
     */
    function current_branch(): ?Branch
    {
        $branchId = current_branch_id();

        if (! $branchId) {
            return null;
        }

        return Branch::find($branchId);
    }
}

if (! function_exists('validate_branch_access')) {
    /**
     * Validate that the current user can access the specified branch.
     *
     * @param  int  $branchId
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

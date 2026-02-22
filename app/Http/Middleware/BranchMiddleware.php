<?php
namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Branch;

class BranchMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Validates that:
     * 1. The b_id parameter is a valid UUID that exists in the database
     * 2. The current user is authorized to access this branch
     *    - Super admins can access any branch (or use default if none specified)
     *    - Branch-specific users (manager, supervisor, employee) can only access their assigned branch
     *
     * Uses unified authorization helpers for consistent role checking.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $b_id = $request->query('b_id');
        $user = Auth::user();

        // Use the unified helper to check for super admin status (most robust)
        $isSuperAdmin = function_exists('is_super_admin') ? is_super_admin() : AuthService::isSuperAdmin();

        if ($isSuperAdmin) {
            // MD and other super admins can access all branches
            if (empty($b_id)) {
                // No branch specified - use default
                $defaultBranch = Branch::where('is_active', 1)->first();
                if ($defaultBranch) {
                    $b_id = $defaultBranch->id;
                    set_current_branch($b_id);
                } else {
                    abort(403, 'No active branches available');
                }
            } else {
                // Super admin specified a branch - validate it exists
                $branchExists = Branch::where('id', $b_id)->where('is_active', true)->exists();
                if (!$branchExists) {
                    abort(403, 'Invalid branch');
                }
                set_current_branch($b_id);
            }
        } else {
            // Branch-specific users (Admin) - restricted to their assigned branch
            if (empty($b_id)) {
                // No branch specified - use their assigned branch
                $resolvedBranchId = function_exists('get_user_branch_id') ? get_user_branch_id() : ($user->branch_id ?? null);
                if ($resolvedBranchId) {
                    $b_id = $resolvedBranchId;
                    set_current_branch($b_id);
                } else {
                    abort(403, 'Branch parameter required');
                }
            } else {
                // User specified a branch - it must match their assigned branch
                $resolvedBranchId = function_exists('get_user_branch_id') ? get_user_branch_id() : ($user->branch_id ?? null);
                if ($resolvedBranchId !== $b_id) {
                    abort(403, 'Cannot access this branch');
                }
                set_current_branch($b_id);
            }
        }

        // Validate format and existence
        $validator = Validator::make(['b_id' => $b_id], [
            'b_id' => ['required', 'uuid', 'exists:branches,id'],
        ]);

        if ($validator->fails()) {
            abort(403, 'Invalid branch access');
        }

        // Set the branch globally for the request
        $branch = Branch::find($b_id);
        app()->instance('currentBranch', $branch);

        return $next($request);
    }
}

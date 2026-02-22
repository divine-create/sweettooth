<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetBranchContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Unified system: handle all authenticated users based on roles
        if (Auth::check()) {
            $user = Auth::user();

            if (is_super_admin()) {
                // Super Admin: Session-based branch selection with URL override
                $this->setSuperAdminBranchContext($request);
                $redirect = $this->ensureBranchSlugInUrl($request);
                if ($redirect) {
                    return $redirect;
                }
            } else {
                // Regular users: Branch-specific context
                $this->setUserBranchContext($request);
            }
        }

        return $next($request);
    }

    /* --------------------------------------------------------------------- *
     *  Super Admin: Session-based branch selection with URL override
     *  
     *  Logic:
     *  1. If b_id is in URL query parameter, validate and use it
     *  2. Otherwise, use session (or set default if no session)
     *  3. Validate that selected branch exists and is active
     * --------------------------------------------------------------------- */
    protected function setSuperAdminBranchContext(Request $request): void
    {
        // Check if b_id is explicitly provided in the URL (super admin selecting a branch)
        $urlBranchId = $request->query('b_id');
        
        if ($urlBranchId) {
            // Super admin is trying to select a specific branch via URL
            // Validate it's a real, active branch
            $branch = Branch::where('id', $urlBranchId)
                ->where('is_active', 1)
                ->first();
            
            if ($branch) {
                session(['selected_branch_id' => $branch->id]);
            } else {
                // Invalid branch in URL, just ignore and fall back to current session/default
                \Illuminate\Support\Facades\Log::warning('Invalid branch selection attempt by super admin', [
                    'requested_branch' => $urlBranchId,
                    'user_id' => auth()->id(),
                ]);
            }
        } elseif (!session()->has('selected_branch_id')) {
            // No b_id in URL and no session set, use default
            $user = Auth::user();

            // Try to use last accessed branch
            $defaultBranch = $user->last_accessed_branch_id;

            // If no last accessed branch, use first active branch
            if (!$defaultBranch) {
                $firstBranch = Branch::where('is_active', 1)
                    ->orderBy('name')
                    ->first();

                $defaultBranch = $firstBranch?->id;
            }

            if ($defaultBranch) {
                session(['selected_branch_id' => $defaultBranch]);
            }
        }

        // Validate that the selected branch still exists and is active
        $selectedBranch = session('selected_branch_id');
        if ($selectedBranch) {
            $branchExists = Branch::where('id', $selectedBranch)
                ->where('is_active', 1)
                ->exists();

            // If branch no longer exists or is inactive, reset to first available
            if (!$branchExists) {
                $firstBranch = Branch::where('is_active', 1)
                    ->orderBy('name')
                    ->first();

                session(['selected_branch_id' => $firstBranch?->id]);
            }
        }
    }

    /* --------------------------------------------------------------------- *
     *  NEW: Redirect to URL with branch slug if missing
     * --------------------------------------------------------------------- */
    protected function ensureBranchSlugInUrl(Request $request): ?Response
    {
        // Skip slug redirect for appraisal routes since they use b_id parameter
        if ($request->is('branch-dashboard/hr/appraisals/*')) {
            return null;
        }

        $branchId = session('selected_branch_id');

        // No branch selected yet – nothing to redirect to
        if (!$branchId) {
            return null;
        }


        $branch = Branch::select('id')
            ->where('id', $branchId)
            ->where('is_active', 1)
            ->first();

        // Fallback if the stored branch disappeared
        if (!$branch) {
            $branch = Branch::where('is_active', 1)
                ->orderBy('name')
                ->first();

            if ($branch) {
                session(['selected_branch_id' => $branch->id]);
            } else {
                return null;
            }
        }

        // Check if the current route already contains the correct slug
        $currentSlug = $request->route('branch_slug'); // <-- adjust to your route param name

        if ($currentSlug === $branch->slug) {
            return null; // URL already correct
        }

        // Build URL with the correct slug
        $newUrl = $this->buildUrlWithBranchSlug($request, $branch->slug);

        return redirect($newUrl, 302);
    }

    /* --------------------------------------------------------------------- *
     *  Helper: rebuild URL with branch slug
     * --------------------------------------------------------------------- */
    protected function buildUrlWithBranchSlug(Request $request, string $slug): string
    {
        $routeName = $request->route()?->getName();

        if ($routeName) {
            // Preserve all existing route parameters
            $params = $request->route()->parameters();
            $params['branch_slug'] = $slug;

            return route($routeName, $params);
        }

        // Fallback for non-named routes – replace first segment
        $uri    = $request->getPathInfo();
        $query  = $request->server('QUERY_STRING');
        $segments = explode('/', trim($uri, '/'));
        $segments[0] = $slug;
        $newPath = '/' . implode('/', $segments);

        return $query ? $newPath . '?' . $query : $newPath;
    }

    /* --------------------------------------------------------------------- *
     *  Employee: Fixed to assigned branch - NO branch selection allowed
     *  
     *  Logic:
     *  1. Employee MUST use their assigned branch_id from the database
     *  2. If they try to access a different branch via ?b_id=xxx, it's logged
     *  3. Session is ALWAYS set to their actual branch_id
     *  4. The BranchMiddleware will validate this on the route
     * --------------------------------------------------------------------- */
    protected function setUserBranchContext(Request $request): void
    {
        $employee = Auth::user();

        if ($employee && $employee->branch_id) {
            // Check if employee is trying to access a different branch via URL parameter
            $requestedBranchId = $request->query('b_id');
            if ($requestedBranchId && $requestedBranchId !== $employee->branch_id) {
                \Illuminate\Support\Facades\Log::warning('Employee attempted to access unauthorized branch', [
                    'employee_id' => $employee->id,
                    'assigned_branch' => $employee->branch_id,
                    'requested_branch' => $requestedBranchId,
                    'url' => $request->fullUrl(),
                ]);
            }

            // ALWAYS set session to employee's actual assigned branch
            // This ensures they can only access their branch regardless of URL parameters
            session(['selected_branch_id' => $employee->branch_id]);

            if ($employee->department_id) {
                session(['selected_department_id' => $employee->department_id]);
            }
        }
    }
}
<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated (unified system)
        if (Auth::check()) {
            $user = Auth::user();

            // Redirect based on role
            if (is_super_admin()) {
                // Super admin - redirect to branch dashboard with default branch
                return redirect($this->getSuperAdminRedirectUrl($user));
            } else {
                // Regular user - redirect to their branch dashboard
                if ($user->branch_id) {
                    return redirect()->route('branch-dashboard.index', ['b_id' => $user->branch_id]);
                } else {
                    // No branch assigned - redirect to branch selection
                    return redirect()->route('branch-select');
                }
            }
        }

        return $next($request);
    }

    /**
     * Get the redirect URL for super-admin.
     */
    private function getSuperAdminRedirectUrl($user): string
    {
        // Try to use last accessed branch
        $branch = Branch::where('id', $user->last_accessed_branch_id)
            ->where('is_active', 1)
            ->first();

        // Fall back to first available branch
        if (!$branch) {
            $branch = Branch::where('is_active', 1)
                ->orderBy('created_at')
                ->first();
        }

        // If branch exists, redirect to branch-dashboard with b_id
        if ($branch) {
            return route('branch-dashboard.index', ['b_id' => $branch->id]);
        }

        // Fallback if no branches available
        return route('branch-select');
    }
}

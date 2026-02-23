<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminOrPermission
{
    /**
     * Handle an incoming request.
     *
     * Allows access if user is super admin OR has any of the specified permissions.
     * Uses unified authorization helpers for consistent checking.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        // Allow super admins always
        if (is_super_admin()) {
            return $next($request);
        }

        // Check if user has any of the required permissions or roles
        foreach ($permissions as $permission) {
            if (has_permission($permission) || has_role($permission)) {
                return $next($request);
            }
        }

        abort(403, 'Insufficient permissions');
    }
}

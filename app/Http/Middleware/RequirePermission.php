<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    /**
     * Handle an incoming request.
     *
     * Ensures the authenticated user has the specified permission.
     * Uses unified authorization helpers for consistent permission checking.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!has_permission($permission)) {
            abort(403, "Permission '{$permission}' required");
        }

        return $next($request);
    }
}
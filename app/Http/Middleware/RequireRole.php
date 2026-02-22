<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    /**
     * Handle an incoming request.
     *
     * Ensures the authenticated user has the specified role.
     * Uses unified authorization helpers for consistent role checking.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!has_role($role)) {
            abort(403, "Role '{$role}' required");
        }

        return $next($request);
    }
}
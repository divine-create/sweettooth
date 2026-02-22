<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * Only super admin users can pass through this middleware.
     * Uses unified authorization helper for consistent role checking.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Use unified helper - super admin role required
        if (!is_super_admin()) {
            abort(403, 'Super admin access required');
        }

        return $next($request);
    }
}

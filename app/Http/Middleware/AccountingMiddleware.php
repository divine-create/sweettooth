<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountingMiddleware
{
    /**
     * Handle an incoming request for accounting access
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user has accounting role
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Check if user has accounting-related roles
        if (!$this->hasAccountingAccess(auth()->user())) {
            abort(403, 'Unauthorized access to accounting system');
        }

        // Log access
        \Log::info('Accounting system access', [
            'user_id' => auth()->id(),
            'path' => $request->path(),
            'method' => $request->method(),
        ]);

        return $next($request);
    }

    /**
     * Check if user has accounting access
     */
    private function hasAccountingAccess($user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->can('view-accounting') || $user->can('manage-accounting')) {
            return true;
        }

        $accountingRoles = ['Accountant', 'Accounting Manager', 'Admin', 'Super Admin'];

        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole($accountingRoles);
        }

        // Fallback check
        return true; // Allow all authenticated users (customize as needed)
    }
}

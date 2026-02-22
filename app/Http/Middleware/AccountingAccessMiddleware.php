<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountingAccessMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * Allows Accounting Manager role to access production data from all departments
     * within their branch.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if ($user && $user->hasRole('Accounting Manager')) {
            // Mark user as having cross-department access for accounting purposes
            $request->attributes->set('is_accounting_manager', true);
        }
        
        return $next($request);
    }
}

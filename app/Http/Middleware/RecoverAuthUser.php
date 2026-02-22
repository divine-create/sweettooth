<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RecoverAuthUser
{
    /**
     * Handle an incoming request.
     * 
     * Detects and recovers from serialized/corrupted user data in sessions.
     * This can happen when switching between guards or after database migrations.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the authenticated user is a string (serialized data)
        $user = Auth::user();
        
        if ($user && is_string($user)) {
            // User data is corrupted/serialized
            \Log::warning('Detected serialized user in session, attempting recovery', [
                'serialized_value' => substr($user, 0, 50),
                'ip' => $request->ip(),
            ]);
            
            // Try to recover user ID
            $userId = null;
            
            // Check if it looks like a UUID
            if (strlen($user) === 36 && substr_count($user, '-') === 4) {
                $userId = $user;
            } elseif (is_numeric($user)) {
                $userId = $user;
            }
            
            // Attempt to load user and re-authenticate
            if ($userId) {
                $recoveredUser = \App\Models\User::find($userId);
                if ($recoveredUser) {
                    // Re-authenticate with fresh user data
                    Auth::setUser($recoveredUser);
                    \Log::info('Successfully recovered user from serialized session', [
                        'user_id' => $userId,
                    ]);
                    return $next($request);
                }
            }
            
            // Recovery failed - logout
            \Log::error('Failed to recover user from session, forcing logout', [
                'serialized_value' => substr($user, 0, 50),
                'ip' => $request->ip(),
            ]);
            Auth::logout();
            
            return redirect()->route('login')->with('error', 'Session corrupted. Please login again.');
        }
        
        return $next($request);
    }
}

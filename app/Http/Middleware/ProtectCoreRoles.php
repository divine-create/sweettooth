<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\AuthService;
use Spatie\Permission\Models\Role;

class ProtectCoreRoles
{
    /**
     * Handle an incoming request.
     * Only Super Admins can access core role management
     */
    public function handle(Request $request, Closure $next)
    {
        AuthService::requireSuperAdmin();

        // Additional validation if role_id is in route
        if ($request->route('role_id')) {
            $roleId = $request->route('role_id');
            $role = Role::find($roleId);

            if (!$role) {
                abort(404, 'Role not found');
            }

            // Prevent modifying protected roles
            if ($role->is_protected) {
                abort(403, "Cannot modify protected role: {$role->name}");
            }
        }

        return $next($request);
    }
}

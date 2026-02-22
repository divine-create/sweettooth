<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role Level Middleware
 *
 * Checks if user has minimum role level for an action.
 * Usage: ->middleware('role.level:3') for Manager+ access
 *
 * Levels:
 * 5 = Super Admin
 * 4 = Admin
 * 3 = Manager
 * 2 = Supervisor
 * 1 = Staff
 */
class RoleLevelMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, int $minLevel): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $userLevel = $this->getUserLevel($user);

        if ($userLevel < $minLevel && ! $this->hasPermissionForLevel($user, $minLevel)) {
            $levelNames = [
                1 => 'Staff',
                2 => 'Supervisor',
                3 => 'Manager',
                4 => 'Admin',
                5 => 'Super Admin',
            ];

            $requiredRole = $levelNames[$minLevel] ?? "Level $minLevel";
            abort(403, "This action requires {$requiredRole} access or higher.");
        }

        // Add role level to request for use in controllers/views
        $request->attributes->set('user_role_level', $userLevel);

        return $next($request);
    }

    /**
     * Allow permission-based access when roles are missing/misaligned.
     */
    private function hasPermissionForLevel(User $user, int $minLevel): bool
    {
        if (!method_exists($user, 'hasAnyPermission')) {
            return false;
        }

        $permissionMap = [
            5 => ['manage-branches', 'manage-roles', 'manage-settings'],
            4 => ['manage-organization', 'manage-branches'],
            3 => ['manage-inventory', 'manage-sales', 'manage-production', 'manage-accounting', 'manage-departments', 'view-analytics'],
            2 => ['view-inventory', 'view-sales', 'view-production', 'view-accounting', 'view-employees', 'manage-employees', 'manage-leave', 'view-reports'],
        ];

        $permissions = $permissionMap[$minLevel] ?? [];
        if (empty($permissions)) {
            return false;
        }

        return $user->hasAnyPermission($permissions);
    }

    /**
     * Get user's role level
     */
    private function getUserLevel(User $user): int
    {
        // Legacy fast-path: user_type admin is treated as Super Admin
        if (isset($user->user_type) && $user->user_type === 'admin') {
            return 5;
        }

        // Check by role level column first (new system)
        $role = $user->roles()->orderByDesc('level')->first();

        if ($role && isset($role->level)) {
            return (int) $role->level;
        }

        // Fallback: check by role name (old system compatibility)
        if ($user->hasRole('Super Admin')) return 5;
        if ($user->hasRole('Admin')) return 4;

        $managerRoles = [
            'Managing Director', 'Admin', 'Accounting Manager',
            'Production Manager', 'Sales Manager', 'HR Manager',
            'Inventory Manager', 'Head Chef', 'Gelato Chef', 'Floor Manager',
        ];
        if ($user->hasAnyRole($managerRoles)) return 3;

        $supervisorRoles = [
            'HR Officer', 'Accountant', 'Cost Accountant', 'Till Supervisor', 'Cornerstore Supervisor',
            'Consession Supervisor', 'Coffee Barista Trainer', 'Lobby Host Supervisor', 'Kitchen Assistant Supervisor',
            'Hot Kitchen Chef', 'Pastry Chef', 'Assistant Shop Floor Manager', 'Inventory Team Lead',
            'Procurement Officer', 'Facility Officer', 'Cleaners Supervisor', 'Chief Security Officer',
            'Social Media Manager',
        ];
        if ($user->hasAnyRole($supervisorRoles)) return 2;

        return 1;
    }
}

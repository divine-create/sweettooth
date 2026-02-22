<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

/**
 * Unified Authentication Service
 * Single source of truth for all user authentication and authorization checks
 * 
 * ALWAYS use this service instead of calling hasAnyRole() or auth() guards directly
 */
class AuthService
{
    /**
     * Get current authenticated user (unified system - web guard only)
     */
    public static function user()
    {
        return Auth::guard('web')->user();
    }

    /**
     * Check if user is authenticated
     */
    public static function check(): bool
    {
        return Auth::guard('web')->check();
    }

    /**
     * Check if user is super admin (role-based in unified system)
     * 
     * Super admins have any of these roles:
     * - super-admin / Super Admin / super_admin
     * - Managing Director
     * - admin / Admin (legacy)
     */
    public static function isSuperAdmin(): bool
    {
        if (function_exists('is_super_admin')) {
            return is_super_admin();
        }

        $user = self::user();
        if (!$user) {
            return false;
        }

        if (isset($user->user_type) && $user->user_type === 'admin') {
            return true;
        }

        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole([
                'super-admin', 'Super Admin', 'super_admin',
                'SuperAdmin',
                'Managing Director',
                'admin', 'Admin'
            ]);
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('Super Admin')
                || $user->hasRole('super-admin')
                || $user->hasRole('super_admin')
                || $user->hasRole('SuperAdmin')
                || $user->hasRole('Managing Director')
                || $user->hasRole('admin')
                || $user->hasRole('Admin');
        }

        return false;
    }

    /**
     * Check if user has specific role
     */
    public static function hasRole(string|array $roles): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        return $user->hasAnyRole($roles);
    }

    /**
     * Check if user has specific permission
     */
    public static function hasPermission(string|array $permissions): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        return $user->hasAnyPermission($permissions);
    }

    /**
     * Get current user's roles
     */
    public static function getRoles(): array
    {
        $user = self::user();
        if (!$user) {
            return [];
        }

        return $user->roles()->pluck('name')->toArray();
    }

    /**
     * Get current guard name (unified system)
     */
    public static function guard(): string
    {
        if (Auth::guard('web')->check()) {
            return 'web';
        }
        return 'none';
    }

    /**
     * Check if user is employee (role-based in unified system)
     */
    public static function isEmployee(): bool
    {
        $user = self::user();
        return (bool) $user && ! self::isSuperAdmin();
    }

    /**
     * Ensure user is authenticated, throw 401 if not
     */
    public static function requireAuth(): void
    {
        if (!self::check()) {
            abort(401, 'Unauthorized');
        }
    }

    /**
     * Ensure user is super admin, throw 403 if not
     */
    public static function requireSuperAdmin(): void
    {
        if (!self::isSuperAdmin()) {
            abort(403, 'Only Super Admins can access this resource');
        }
    }

    /**
     * Ensure user has role, throw 403 if not
     */
    public static function requireRole(string|array $roles): void
    {
        if (!self::hasRole($roles)) {
            abort(403, 'Insufficient permissions');
        }
    }

    /**
     * Ensure user has permission, throw 403 if not
     */
    public static function requirePermission(string|array $permissions): void
    {
        if (!self::hasPermission($permissions)) {
            abort(403, 'Insufficient permissions');
        }
    }
}

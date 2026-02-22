<?php

namespace App\Policies;

use App\Enums\CallbackStatus;
use App\Models\ProductDispatchCallback;
use App\Models\User;
use App\Services\SidebarVisibilityService;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Authorization policy for ProductDispatchCallback actions
 *
 * Defines who can perform various actions on product dispatch callbacks:
 * - approve: Production managers and supervisors
 * - receive: Production staff and above
 * - complete: Production managers and above
 * - view: Anyone with production access
 */
class ProductDispatchCallbackPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any callbacks
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'view-production',
            'manage-production',
            'view-sales',
            'manage-sales',
        ]) || SidebarVisibilityService::canSeeProduction($user);
    }

    /**
     * Determine if user can view a specific callback
     */
    public function view(User $user, ProductDispatchCallback $callback): bool
    {
        // Super Admin can view all
        if (SidebarVisibilityService::isSuperAdmin()) {
            return true;
        }

        // Check if user has general view permission
        if (!$this->viewAny($user)) {
            return false;
        }

        // Check branch access
        $callbackBranchId = $callback->salesShift?->branch_id
            ?? $callback->productDispatch?->salesShift?->branch_id;

        if ($callbackBranchId && $user->branch_id) {
            return $user->branch_id === $callbackBranchId;
        }

        return true;
    }

    /**
     * Determine if user can approve a callback
     *
     * Only production managers, supervisors, and above can approve
     */
    public function approve(User $user, ProductDispatchCallback $callback): bool
    {
        // Super Admin can approve all
        if (SidebarVisibilityService::isSuperAdmin()) {
            return true;
        }

        // Must have at least supervisor level (level 2+)
        $roleLevel = SidebarVisibilityService::getRoleLevel($user);
        if ($roleLevel < SidebarVisibilityService::LEVEL_SUPERVISOR) {
            return false;
        }

        // Check specific permission
        if ($user->hasAnyPermission(['manage-production'])) {
            return $this->view($user, $callback);
        }

        return false;
    }

    /**
     * Determine if user can mark a callback as received
     *
     * Production staff and above can receive callbacks
     */
    public function receive(User $user, ProductDispatchCallback $callback): bool
    {
        // Super Admin can receive all
        if (SidebarVisibilityService::isSuperAdmin()) {
            return true;
        }

        // Check specific permission
        if ($user->hasAnyPermission(['manage-production', 'view-production'])) {
            return $this->view($user, $callback);
        }

        return false;
    }

    /**
     * Determine if user can complete a callback with stock updates
     *
     * Only production managers and above can complete
     */
    public function complete(User $user, ProductDispatchCallback $callback): bool
    {
        // Super Admin can complete all
        if (SidebarVisibilityService::isSuperAdmin()) {
            return true;
        }

        // Must have at least manager level (level 3+)
        $roleLevel = SidebarVisibilityService::getRoleLevel($user);
        if ($roleLevel < SidebarVisibilityService::LEVEL_MANAGER) {
            return false;
        }

        // Check specific permission
        if ($user->hasAnyPermission(['manage-production'])) {
            return $this->view($user, $callback);
        }

        return false;
    }

    /**
     * Determine if user can create a callback
     */
    public function create(User $user): bool
    {
        return $user->hasAnyPermission([
            'manage-sales',
            'process-sales',
            'view-sales',
            'manage-production',
        ]) || SidebarVisibilityService::canSeeSalesManagement($user);
    }

    /**
     * Determine if user can delete a callback
     *
     * Only admins can delete callbacks (and only if pending)
     */
    public function delete(User $user, ProductDispatchCallback $callback): bool
    {
        // Only pending callbacks can be deleted
        if ($callback->status !== CallbackStatus::PENDING) {
            return false;
        }

        // Super Admin can delete
        if (SidebarVisibilityService::isSuperAdmin()) {
            return true;
        }

        // Must be admin level
        $roleLevel = SidebarVisibilityService::getRoleLevel($user);
        return $roleLevel >= SidebarVisibilityService::LEVEL_ADMIN;
    }
}

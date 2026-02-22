<?php

namespace App\Policies;

use App\Models\GlEntry;
use App\Models\User;
use App\Services\SidebarVisibilityService;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Authorization policy for General Ledger Entries
 *
 * Defines who can perform various actions on GL entries:
 * - create: Accountants and finance roles
 * - update: Accountants for draft entries only
 * - delete: Finance managers for draft entries only
 * - post: Implements segregation of duties
 * - reverse: Senior staff only
 */
class GlEntryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any GL entries
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'view-accounting',
            'manage-accounting',
        ]) || $user->hasAnyRole(['Accountant', 'Accounting Manager', 'Admin', 'Super Admin']);
    }

    /**
     * Determine if user can view a specific GL entry
     */
    public function view(User $user, GlEntry $entry): bool
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
        if ($entry->branch_id && $user->branch_id) {
            return $user->branch_id === $entry->branch_id;
        }

        return true;
    }

    /**
     * Determine if user can create GL entries
     */
    public function create(User $user): bool
    {
        return $user->hasAnyPermission([
            'manage-accounting',
        ]) || $user->hasAnyRole(['Accountant', 'Accounting Manager', 'Admin', 'Super Admin']);
    }

    /**
     * Determine if user can update a GL entry
     */
    public function update(User $user, GlEntry $entry): bool
    {
        // Cannot update posted or reversed entries
        if (in_array($entry->status, ['posted', 'reversed'])) {
            return false;
        }

        // Super Admin can update all draft entries
        if (SidebarVisibilityService::isSuperAdmin()) {
            return true;
        }

        // Check permission
        if (!$user->hasAnyPermission([
            'manage-accounting',
        ]) && !$user->hasAnyRole(['Accountant', 'Accounting Manager'])) {
            return false;
        }

        // Check branch access
        if ($entry->branch_id && $user->branch_id) {
            return $user->branch_id === $entry->branch_id;
        }

        return true;
    }

    /**
     * Determine if user can delete a GL entry
     */
    public function delete(User $user, GlEntry $entry): bool
    {
        // Only draft entries can be deleted
        if ($entry->status !== 'draft') {
            return false;
        }

        // Super Admin can delete
        if (SidebarVisibilityService::isSuperAdmin()) {
            return true;
        }

        // Only finance managers and above can delete
        if (!$user->hasAnyPermission([
            'manage-accounting',
        ]) && !$user->hasAnyRole(['Accounting Manager', 'Admin'])) {
            return false;
        }

        // Check branch access
        if ($entry->branch_id && $user->branch_id) {
            return $user->branch_id === $entry->branch_id;
        }

        return true;
    }

    /**
     * Determine if user can post a GL entry
     *
     * Implements segregation of duties: creator cannot post their own entries
     * unless they have special permission
     */
    public function post(User $user, GlEntry $entry): bool
    {
        // Only draft entries can be posted
        if ($entry->status !== 'draft') {
            return false;
        }

        // Super Admin can always post
        if (SidebarVisibilityService::isSuperAdmin()) {
            return true;
        }

        // Check basic posting permission
        if (!$user->hasAnyPermission([
            'manage-accounting',
        ]) && !$user->hasAnyRole(['Accountant', 'Accounting Manager'])) {
            return false;
        }

        // Segregation of duties check
        if ($entry->entered_by_id == $user->id) {
            // Same user can only post their own entries if they have special permission
            if (!$user->hasAnyPermission(['manage-accounting']) &&
                !$user->hasAnyRole(['Accounting Manager', 'Admin'])) {
                return false;
            }
        }

        // Check branch access
        if ($entry->branch_id && $user->branch_id) {
            return $user->branch_id === $entry->branch_id;
        }

        return true;
    }

    /**
     * Determine if user can reverse a posted GL entry
     */
    public function reverse(User $user, GlEntry $entry): bool
    {
        // Only posted entries can be reversed
        if ($entry->status !== 'posted') {
            return false;
        }

        // Super Admin can reverse
        if (SidebarVisibilityService::isSuperAdmin()) {
            return true;
        }

        // Only senior staff can reverse entries
        if (!$user->hasAnyPermission([
            'manage-accounting',
        ]) && !$user->hasAnyRole(['Accounting Manager', 'Admin'])) {
            return false;
        }

        // Check branch access
        if ($entry->branch_id && $user->branch_id) {
            return $user->branch_id === $entry->branch_id;
        }

        return true;
    }

    /**
     * Determine if user can approve journal entries (for approval workflow)
     */
    public function approve(User $user, GlEntry $entry): bool
    {
        // Only pending_approval entries can be approved
        if ($entry->status !== 'pending_approval') {
            return false;
        }

        // Super Admin can approve
        if (SidebarVisibilityService::isSuperAdmin()) {
            return true;
        }

        // Segregation of duties: cannot approve own entries
        if ($entry->entered_by_id == $user->id) {
            return false;
        }

        // Check approval permission
        if (!$user->hasAnyPermission([
            'manage-accounting',
        ]) && !$user->hasAnyRole(['Accounting Manager', 'Admin'])) {
            return false;
        }

        // Check branch access
        if ($entry->branch_id && $user->branch_id) {
            return $user->branch_id === $entry->branch_id;
        }

        return true;
    }
}

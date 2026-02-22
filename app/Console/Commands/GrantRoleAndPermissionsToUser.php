<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class GrantRoleAndPermissionsToUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:grant-role-and-permissions 
                            {email : The email of the user to grant role and permissions to}
                            {--role= : The role to assign to the user}
                            {--permissions=* : The permissions to assign to the user (comma separated)}
                            {--revoke : Revoke role and permissions instead of granting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant specific role and permissions to a user (Level 3: Role and Permissions)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $roleName = $this->option('role');
        $permissions = $this->option('permissions');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        if ($this->option('revoke')) {
            $this->revokeRoleAndPermissions($user, $roleName, $permissions);
        } else {
            $this->grantRoleAndPermissions($user, $roleName, $permissions);
        }

        return 0;
    }

    private function grantRoleAndPermissions($user, $roleName, $permissions)
    {
        $errors = [];

        // Assign role if specified
        if ($roleName) {
            try {
                $role = Role::firstOrCreate([
                    'name' => $roleName,
                    'guard_name' => 'web',
                ], [
                    'name' => $roleName,
                    'guard_name' => 'web',
                ]);

                if (!$user->hasRole($role)) {
                    $user->assignRole($role);
                    $this->info("Successfully assigned role '{$roleName}' to user: {$user->name}");
                } else {
                    $this->info("User {$user->name} already has role: {$roleName}");
                }
            } catch (\Exception $e) {
                $errors[] = "Failed to assign role '{$roleName}': " . $e->getMessage();
            }
        }

        // Assign permissions if specified
        if (!empty($permissions)) {
            // Flatten the permissions array in case it's multidimensional
            if (is_array($permissions) && count($permissions) > 0 && is_array($permissions[0])) {
                $permissions = $permissions[0];
            }

            // Split comma-separated permissions if needed
            $flatPermissions = [];
            foreach ($permissions as $perm) {
                $perms = explode(',', $perm);
                foreach ($perms as $p) {
                    $flatPermissions[] = trim($p);
                }
            }
            
            $permissions = array_filter($flatPermissions); // Remove empty values

            foreach ($permissions as $permissionName) {
                try {
                    $permission = Permission::firstOrCreate([
                        'name' => $permissionName,
                        'guard_name' => 'web',
                    ], [
                        'name' => $permissionName,
                        'guard_name' => 'web',
                    ]);

                    if (!$user->hasDirectPermission($permission)) {
                        $user->givePermissionTo($permission);
                        $this->info("Successfully granted permission '{$permissionName}' to user: {$user->name}");
                    } else {
                        $this->info("User {$user->name} already has permission: {$permissionName}");
                    }
                } catch (\Exception $e) {
                    $errors[] = "Failed to assign permission '{$permissionName}': " . $e->getMessage();
                }
            }
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->error($error);
            }
            return 1;
        }

        if (!$roleName && empty($permissions)) {
            $this->warn("No role or permissions were specified to grant.");
        } else {
            $this->info("Successfully processed role and permissions for user: {$user->name}");
        }
    }

    private function revokeRoleAndPermissions($user, $roleName, $permissions)
    {
        $errors = [];

        // Revoke role if specified
        if ($roleName) {
            try {
                $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
                
                if ($role && $user->hasRole($role)) {
                    $user->removeRole($role);
                    $this->info("Successfully revoked role '{$roleName}' from user: {$user->name}");
                } else {
                    $this->info("User {$user->name} does not have role: {$roleName}");
                }
            } catch (\Exception $e) {
                $errors[] = "Failed to revoke role '{$roleName}': " . $e->getMessage();
            }
        }

        // Revoke permissions if specified
        if (!empty($permissions)) {
            // Flatten the permissions array in case it's multidimensional
            if (is_array($permissions) && count($permissions) > 0 && is_array($permissions[0])) {
                $permissions = $permissions[0];
            }

            // Split comma-separated permissions if needed
            $flatPermissions = [];
            foreach ($permissions as $perm) {
                $perms = explode(',', $perm);
                foreach ($perms as $p) {
                    $flatPermissions[] = trim($p);
                }
            }
            
            $permissions = array_filter($flatPermissions); // Remove empty values

            foreach ($permissions as $permissionName) {
                try {
                    $permission = Permission::where('name', $permissionName)->where('guard_name', 'web')->first();
                    
                    if ($permission && $user->hasDirectPermission($permission)) {
                        $user->revokePermissionTo($permission);
                        $this->info("Successfully revoked permission '{$permissionName}' from user: {$user->name}");
                    } else {
                        $this->info("User {$user->name} does not have direct permission: {$permissionName}");
                    }
                } catch (\Exception $e) {
                    $errors[] = "Failed to revoke permission '{$permissionName}': " . $e->getMessage();
                }
            }
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->error($error);
            }
            return 1;
        }

        if (!$roleName && empty($permissions)) {
            $this->warn("No role or permissions were specified to revoke.");
        } else {
            $this->info("Successfully processed revocation of role and permissions for user: {$user->name}");
        }
    }
}
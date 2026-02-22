<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;

class GrantSuperAdminRoleToUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:grant-super-admin 
                            {email : The email of the user to grant super admin role to}
                            {--revoke : Revoke super admin role instead of granting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant super admin role to a user (Level 2: Super Admin Role)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        if ($this->option('revoke')) {
            $this->revokeSuperAdminRole($user);
        } else {
            $this->grantSuperAdminRole($user);
        }

        return 0;
    }

    private function grantSuperAdminRole($user)
    {
        // Define all possible super admin role names that are recognized by the system
        $superAdminRoles = [
            'Super Admin', 'super-admin', 'super_admin', 'SuperAdmin',
            'MD', 'Managing Director', 'admin', 'Admin'
        ];

        // Check if user already has any super admin role
        foreach ($superAdminRoles as $roleName) {
            if ($user->hasRole($roleName)) {
                $this->info("User {$user->name} already has super admin role: {$roleName}");
                return;
            }
        }

        // Create or get the Super Admin role
        $role = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ], [
            'name' => 'Super Admin',
            'guard_name' => 'web',
            'level' => 5,
            'is_protected' => true,
            'description' => 'Super Administrator with full system access',
        ]);

        $this->info("Granting Super Admin role to user: {$user->name} ({$user->email})");
        
        $user->assignRole($role);
        
        $this->info("Successfully granted Super Admin role to user: {$user->name}");
    }

    private function revokeSuperAdminRole($user)
    {
        // Define all possible super admin role names that should be removed
        $superAdminRoles = [
            'Super Admin', 'super-admin', 'super_admin', 'SuperAdmin',
            'MD', 'Managing Director', 'admin', 'Admin'
        ];

        $removedRoles = [];
        
        foreach ($superAdminRoles as $roleName) {
            if ($user->hasRole($roleName)) {
                $user->removeRole($roleName);
                $removedRoles[] = $roleName;
            }
        }

        if (empty($removedRoles)) {
            $this->info("User {$user->name} does not have any super admin roles to revoke.");
            return;
        }

        $this->info("Successfully revoked the following super admin roles from user {$user->name}: " . implode(', ', $removedRoles));
    }
}
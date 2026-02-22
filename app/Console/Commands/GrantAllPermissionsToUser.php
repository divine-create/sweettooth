<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Permission;

class GrantAllPermissionsToUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:grant-all-permissions 
                            {email : The email of the user to grant all permissions to}
                            {--revoke : Revoke all permissions instead of granting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant all permissions to a user (Level 1: All Access)';

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
            $this->revokeAllPermissions($user);
        } else {
            $this->grantAllPermissions($user);
        }

        return 0;
    }

    private function grantAllPermissions($user)
    {
        $permissions = Permission::all();
        
        $this->info("Granting all {$permissions->count()} permissions to user: {$user->name} ({$user->email})");
        
        $bar = $this->output->createProgressBar($permissions->count());
        $bar->start();

        foreach ($permissions as $permission) {
            if (!$user->hasDirectPermission($permission)) {
                $user->givePermissionTo($permission);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        
        $this->info("Successfully granted all permissions to user: {$user->name}");
    }

    private function revokeAllPermissions($user)
    {
        $permissions = $user->permissions;
        
        if ($permissions->isEmpty()) {
            $this->info("User {$user->name} has no direct permissions to revoke.");
            return;
        }

        $this->info("Revoking all {$permissions->count()} direct permissions from user: {$user->name} ({$user->email})");
        
        $bar = $this->output->createProgressBar($permissions->count());
        $bar->start();

        foreach ($permissions as $permission) {
            $user->revokePermissionTo($permission);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        
        $this->info("Successfully revoked all direct permissions from user: {$user->name}");
    }
}
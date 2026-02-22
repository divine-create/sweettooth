<?php

require_once __DIR__.'/vendor/autoload.php';

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// Initialize Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get the manage_organization permission
$manageOrgPermission = Permission::firstOrCreate(
    ['name' => 'manage_organization', 'guard_name' => 'web'],
    ['description' => 'Manage organization (employees, departments)', 'category' => 'organization']
);

// Get HR roles and assign the permission
$hrManagerRole = Role::where('name', 'HR Manager')->first();
if ($hrManagerRole) {
    $hrManagerRole->givePermissionTo($manageOrgPermission);
    echo "Added manage_organization permission to HR Manager\n";
}

$hrOfficerRole = Role::where('name', 'HR Officer')->first();
if ($hrOfficerRole) {
    $hrOfficerRole->givePermissionTo($manageOrgPermission);
    echo "Added manage_organization permission to HR Officer\n";
}

echo "HR dashboard access permissions updated successfully!\n";
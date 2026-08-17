<?php
require '/var/www/sweettooth/vendor/autoload.php';
$app = require '/var/www/sweettooth/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$roles = ['Accountant', 'Accounting Manager', 'Cost Accountant'];
foreach($roles as $roleName) {
    $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
    if ($role) {
        $perms = $role->permissions->pluck('name')->toArray();
        echo $roleName . " has permissions:\n  - " . implode("\n  - ", $perms) . "\n\n";
    } else {
        echo $roleName . ": Role Not Found\n\n";
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Department;
use App\Models\DepartmentCategory;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SeedPortHarcourtFoundation extends Command
{
    protected $signature = 'seed:portharcourt-foundation
        {--force : Force re-creation of existing data}
        {--password= : Default password for all users (default: password)}';

    protected $description = 'Seed Port Harcourt branch, categories, departments, and super admin user';

    public function handle(): int
    {
        $force = $this->option('force');
        $password = $this->option('password') ?: 'password';

        $this->info("========================================");
        $this->info("  SweetTooth Port Harcourt Foundation");
        $this->info("========================================");
        $this->newLine();

        // Step 1: Seed Branch
        $this->info("[Step 1/4] Seeding Port Harcourt Branch...");
        $branch = $this->seedBranch($force);
        if (! $branch) {
            $this->error('Failed to create branch. Use --force to overwrite.');
            return 1;
        }
        $this->info("  ✓ Branch: {$branch->name} ({$branch->code})");
        $this->newLine();

        // Step 2: Seed Department Categories
        $this->info("[Step 2/4] Seeding Department Categories...");
        $categories = $this->seedCategories($force);
        $this->info("  ✓ Categories: " . count($categories));
        $this->newLine();

        // Step 3: Seed Departments
        $this->info("[Step 3/4] Seeding Departments...");
        $departments = $this->seedDepartments($branch, $categories, $force);
        $this->info("  ✓ Departments: " . count($departments));
        foreach ($departments as $dept) {
            $this->line("    - {$dept->name}");
        }
        $this->newLine();

        // Step 4: Create Super Admin User
        $this->info("[Step 4/4] Creating Super Admin User...");
        $superAdmin = $this->createSuperAdmin($branch, $password, $force);
        if ($superAdmin) {
            $this->info("  ✓ Super Admin: {$superAdmin->name}");
            $this->info("    Email: {$superAdmin->email}");
            $this->info("    Password: {$password}");
        }
        $this->newLine();

        $this->info("========================================");
        $this->info("  Foundation Seeding Complete!");
        $this->info("========================================");
        $this->newLine();
        $this->info("Next steps:");
        $this->info("  1. Run: php artisan uoms:seed");
        $this->info("  2. Run: php artisan import:hr-users --branch-id={$branch->id} --password={$password}");
        $this->info("  3. Run: php artisan import:all-production-data --branch-id={$branch->id}");
        $this->newLine();

        return 0;
    }

    protected function seedBranch(bool $force): ?Branch
    {
        // Find existing branch by code (including soft deleted)
        $branch = Branch::withTrashed()->where('code', 'PHC-002')->first();
        
        if ($branch) {
            // Restore if soft deleted
            if ($branch->trashed()) {
                $branch->restore();
                $this->info("  ✓ Restored branch: {$branch->name} ({$branch->code})");
            } else {
                $this->info("  ✓ Branch exists: {$branch->name} ({$branch->code})");
            }
            return $branch;
        }
        
        // Create if doesn't exist
        $this->info("  Creating branch...");
        return Branch::create([
            'code' => 'PHC-002',
            'name' => 'SweetTooth Port Harcourt',
            'location' => '78 Trans Amadi Industrial Layout',
            'phone' => '+234-803-456-7890',
            'email' => 'portharcourt@sweettooth.com',
            'description' => 'Port Harcourt main branch',
            'country' => 'Nigeria',
            'state' => 'Rivers',
            'city' => 'Port Harcourt',
            'postal_code' => '500001',
            'timezone' => 'Africa/Lagos',
            'is_active' => true,
        ]);
    }

    protected function seedCategories(bool $force): array
    {
        $definitions = [
            'production' => ['name' => 'Production', 'description' => 'Departments responsible for preparing products.'],
            'sales' => ['name' => 'Sales', 'description' => 'Departments responsible for sales operations.'],
            'support' => ['name' => 'Support', 'description' => 'Support departments for shared operations.'],
        ];

        $categories = [];
        foreach ($definitions as $key => $def) {
            $existing = DepartmentCategory::where('name', $def['name'])->first();
            
            if ($existing) {
                if ($force) {
                    $existing->update($def);
                }
                $categories[$key] = $existing;
            } else {
                $categories[$key] = DepartmentCategory::create($def);
            }
        }

        return $categories;
    }

    protected function seedDepartments(Branch $branch, array $categories, bool $force): array
    {
        $definitions = [
            'hot_kitchen' => [
                'name' => 'Hot Kitchen Production',
                'category_id' => $categories['production']->id,
                'description' => 'Handles hot-kitchen production workflows.',
            ],
            'pastry' => [
                'name' => 'Pastry Production',
                'category_id' => $categories['production']->id,
                'description' => 'Handles pastry production workflows.',
            ],
            'gelato' => [
                'name' => 'Gelato Production',
                'category_id' => $categories['production']->id,
                'description' => 'Handles gelato production workflows.',
            ],
            'corner_store' => [
                'name' => 'Corner Store',
                'category_id' => $categories['sales']->id,
                'description' => 'Corner store sales operations.',
            ],
            'till_sales' => [
                'name' => 'Till Sales',
                'category_id' => $categories['sales']->id,
                'description' => 'Till sales operations.',
            ],
            'concession' => [
                'name' => 'Concession',
                'category_id' => $categories['sales']->id,
                'description' => 'Concession sales operations.',
            ],
            'inventory' => [
                'name' => 'Inventory/Store',
                'category_id' => $categories['support']->id,
                'description' => 'Inventory and stock control.',
            ],
            'hr' => [
                'name' => 'HR',
                'category_id' => $categories['support']->id,
                'description' => 'Human resources support.',
            ],
        ];

        $departments = [];
        foreach ($definitions as $key => $def) {
            $slug = Str::slug($def['name']);
            $existing = Department::where('branch_id', $branch->id)
                ->where('slug', $slug)
                ->first();

            if ($existing) {
                if ($force) {
                    $existing->update($def);
                }
                $departments[$key] = $existing;
            } else {
                $departments[$key] = Department::create([
                    'branch_id' => $branch->id,
                    'slug' => $slug,
                    ...$def,
                ]);
            }
        }

        return $departments;
    }

    protected function createSuperAdmin(Branch $branch, string $password, bool $force): ?User
    {
        $existing = User::where('email', 'admin@sweettooth.local')->first();
        
        if ($existing) {
            if ($force) {
                $this->warn("  Re-creating existing super admin...");
                $existing->delete();
            } else {
                $this->info("  Using existing super admin...");
                // Ensure super admin role is assigned
                $superAdminRole = Role::where('name', 'Super Admin')->first();
                if ($superAdminRole && ! $existing->hasRole($superAdminRole)) {
                    $existing->assignRole($superAdminRole);
                }
                return $existing;
            }
        }

        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@sweettooth.local',
            'password' => Hash::make($password),
            'branch_id' => $branch->id,
            'last_accessed_branch_id' => $branch->id,
            'is_active' => true,
            'email_verified_at' => now(),
            'user_type' => 'employee',
            'employment_status' => 'active',
        ]);

        // Assign Super Admin role
        $superAdminRole = Role::where('name', 'Super Admin')->first();
        if ($superAdminRole) {
            $user->assignRole($superAdminRole);
        }

        return $user;
    }
}

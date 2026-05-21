<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DepartmentCategory;
use App\Models\DepartmentPage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DepartmentPageSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get the Production category
        $productionCategory = DepartmentCategory::where('name', '=', 'Production')->first();

        if (! $productionCategory) {
            $this->command->warn('Production category not found. Please create a Production category first.');

            return;
        }

        $this->command->info("Found category: {$productionCategory->name} (ID: {$productionCategory->id})");

        $departments = Department::where('category_id', $productionCategory->id)->get();

        if ($departments->isEmpty()) {
            $this->command->warn('No production departments found. Please create production departments first.');

            return;
        }

        $this->command->info("Found {$departments->count()} production departments");

        foreach ($departments as $department) {
            // Generate slug if not exists
            if (empty($department->slug)) {
                $department->slug = Str::slug($department->name);
                $department->save();
                $this->command->info("Generated slug '{$department->slug}' for department: {$department->name}");
            }

            // Seed default pages for this department
            $this->seedDepartmentPages($department);

            $this->command->info("✓ Seeded pages for department: {$department->name}");
        }

        $this->command->info('✓ Department pages seeded successfully!');
    }

    /**
     * Seed pages for a specific department
     */
    protected function seedDepartmentPages(Department $department): void
    {
        $deptSlug = $department->slug;

        $pages = [
            // Products Management
            [
                'name' => 'Products',
                'slug' => 'products',
                'route_name' => 'branch-dashboard.production.products',
                'icon' => 'cube',
                'order' => 1,
            ],
            [
                'name' => 'Product Types',
                'slug' => 'product-types',
                'route_name' => 'branch-dashboard.production.product-types',
                'icon' => 'tag',
                'order' => 2,
            ],

            // Recipe Management
            [
                'name' => 'Recipes',
                'slug' => 'recipes',
                'route_name' => 'branch-dashboard.production.recipes.index',
                'icon' => 'book-open',
                'order' => 3,
            ],
            [
                'name' => 'Add Recipe',
                'slug' => 'recipes-add',
                'route_name' => 'branch-dashboard.production.recipes.add',
                'icon' => 'plus-circle',
                'order' => 4,
            ],
            [
                'name' => 'Edit Recipe',
                'slug' => 'recipes-edit',
                'route_name' => 'branch-dashboard.production.recipes.edit',
                'icon' => 'pencil',
                'order' => 5,
            ],
            [
                'name' => 'Recipe Detail',
                'slug' => 'recipes-detail',
                'route_name' => 'branch-dashboard.production.recipes.detail',
                'icon' => 'document-text',
                'order' => 6,
            ],

            // Inventory & Tracking
            [
                'name' => 'Raw Material Tracking',
                'slug' => 'raw-material-tracking',
                'route_name' => 'branch-dashboard.production.raw-material-tracking',
                'icon' => 'chart-bar',
                'order' => 9,
            ],

            // // Module & Stock Monitor
            // [
            //     'name' => 'Module Index',
            //     'slug' => 'module-index',
            //     'route_name' => "branch-dashboard.production.module.index",
            //     'icon' => 'view-grid',
            //     'order' => 10,
            // ],
            // [
            //     'name' => 'Stock Monitor',
            //     'slug' => 'stock-monitor',
            //     'route_name' => "branch-dashboard.production.module.stock-monitor",
            //     'icon' => 'eye',
            //     'order' => 11,
            // ],

            // // Request Management
            // [
            //     'name' => 'Create Request',
            //     'slug' => 'request-create',
            //     'route_name' => "branch-dashboard.production.request.create",
            //     'icon' => 'plus',
            //     'order' => 12,
            // ],
            // [
            //     'name' => 'View Requests',
            //     'slug' => 'request-index',
            //     'route_name' => "branch-dashboard.production.request.index",
            //     'icon' => 'inbox',
            //     'order' => 13,
            // ],
        ];

        foreach ($pages as $pageData) {
            DepartmentPage::updateOrCreate(
                [
                    'department_id' => $department->id,
                    'slug' => $pageData['slug'],
                ],
                [
                    'name' => $pageData['name'],
                    'route_name' => $pageData['route_name'],
                    'icon' => $pageData['icon'],
                    'order' => $pageData['order'],
                    'is_active' => true,
                ]
            );
        }
    }
}

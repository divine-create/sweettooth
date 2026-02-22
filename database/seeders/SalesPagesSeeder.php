<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DepartmentCategory;
use App\Models\DepartmentPage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SalesPagesSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get the Production category
        $salesCategory = DepartmentCategory::where('name', '=', 'Sales')->first();

        if (! $salesCategory) {
            $this->command->warn('Sales category not found. Please create a category with name Sales first.');

            return;
        }

        $this->command->info("Found category: {$salesCategory->name} (ID: {$salesCategory->id})");

        $departments = Department::where('category_id', $salesCategory->id)->get();

        if ($departments->isEmpty()) {
            $this->command->warn('No sales departments found. Please create sales departments first.');

            return;
        }

        $this->command->info("Found {$departments->count()} sales departments");

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
            [
                'name' => 'POS',
                'slug' => 'pos',
                'route_name' => 'branch-dashboard.sales-dashboard.pos.index',
                'icon' => 'shopping-cart',
                'order' => 1,
            ],
            [
                'name' => 'My Sales',
                'slug' => 'my-sales',
                'route_name' => 'branch-dashboard.sales-dashboard.my-sales.index',
                'icon' => 'user-circle',
                'order' => 2,
            ],
            [
                'name' => 'Sales Analytics',
                'slug' => 'sales-analytics',
                'route_name' => 'branch-dashboard.sales-dashboard.analytics.index',
                'icon' => 'chart-bar',
                'order' => 3,
            ],
            [
                'name' => 'Shift Closing',
                'slug' => 'shift-closing',
                'route_name' => 'branch-dashboard.sales-dashboard.shift-closing.index',
                'icon' => 'clock',
                'order' => 4,
            ],
            [
                'name' => 'Sales Reports',
                'slug' => 'sales-reports',
                'route_name' => 'branch-dashboard.sales-dashboard.reports.sales-performance',
                'icon' => 'document-text',
                'order' => 5,
            ],
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

<?php

namespace App\Observers;

use App\Models\Department;
use App\Models\DepartmentPage;
use Illuminate\Support\Str;

class DepartmentObserver
{
    /**
     * Handle the Department "created" event.
     */
    public function created(Department $department): void
    {
        // Auto-generate slug if not set
        if (empty($department->slug)) {
            $department->slug = Str::slug($department->name);
            $department->saveQuietly(); // Save without triggering events again
        }

        // Auto-seed default pages for production departments
        $this->seedDefaultPages($department);
    }

    /**
     * Handle the Department "updating" event.
     */
    public function updating(Department $department): void
    {
        // Auto-generate slug when name changes
        if ($department->isDirty('name') && empty($department->slug)) {
            $department->slug = Str::slug($department->name);
        }
    }

    /**
     * Seed default pages for a department
     */
    protected function seedDefaultPages(Department $department): void
    {
        // Check if this is a production department
        if ($department->category && $department->category->name === 'Production') {
            $defaultPages = $this->getDefaultProductionPages($department);

            foreach ($defaultPages as $pageData) {
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

    /**
     * Get default production pages configuration
     */
    protected function getDefaultProductionPages(Department $department): array
    {
        return [
            // Products Management
            [
                'name' => 'Products',
                'slug' => 'products',
                'route_name' => "branch-dashboard.production.products",
                'icon' => 'cube',
                'order' => 1,
            ],
            [
                'name' => 'Product Types',
                'slug' => 'product-types',
                'route_name' => "branch-dashboard.production.product-types",
                'icon' => 'tag',
                'order' => 2,
            ],

            // Recipe Management
            [
                'name' => 'Recipes',
                'slug' => 'recipes',
                'route_name' => "branch-dashboard.production.recipes.index",
                'icon' => 'book-open',
                'order' => 3,
            ],
            [
                'name' => 'Product Assignments',
                'slug' => 'product-assignments',
                'route_name' => "branch-dashboard.production.product-assignments",
                'icon' => 'clipboard-document-check',
                'order' => 4,
            ],

            // Production Operations
            [
                'name' => 'Production Requests',
                'slug' => 'production-requests',
                'route_name' => "branch-dashboard.production.request.index",
                'icon' => 'clipboard',
                'order' => 5,
            ],
            [
                'name' => 'Daily Produce',
                'slug' => 'daily-produce',
                'route_name' => "branch-dashboard.production.daily-produce.index",
                'icon' => 'calendar',
                'order' => 6,
            ],

            // Inventory & Tracking
            [
                'name' => 'Raw Material Tracking',
                'slug' => 'raw-material-tracking',
                'route_name' => "branch-dashboard.production.raw-material-tracking",
                'icon' => 'chart-bar',
                'order' => 7,
            ],

            // Shift Closing
            [
                'name' => 'Shift Closing',
                'slug' => 'shift-closing',
                'route_name' => "branch-dashboard.production.shift-closing.index",
                'icon' => 'clock',
                'order' => 8,
            ],

            // Callbacks Section
            [
                'name' => 'View Callbacks',
                'slug' => 'callbacks-index',
                'route_name' => "branch-dashboard.production.callbacks.index",
                'icon' => 'arrow-path',
                'order' => 9,
            ],
            [
                'name' => 'Approve Sales Callbacks',
                'slug' => 'callbacks-approve',
                'route_name' => "branch-dashboard.production.callbacks.approve-sales-callbacks",
                'icon' => 'check-circle',
                'order' => 10,
            ],


            // Production Reports removed (centralized reporting)
        ];
    }
}

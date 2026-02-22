<?php

namespace App\Observers;

use App\Models\Department;
use Illuminate\Support\Str;
use App\Models\DepartmentPage;

class SalesPageObserver
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
        if ($department->category && $department->category->name === 'Sales') {
            $defaultPages = $this->getDefaultSalesPages($department);

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
    protected function getDefaultSalesPages(Department $department): array
    {
        return [
            [
                'name' => 'Stock Opening',
                'slug' => 'stock-opening',
                'route_name' => "branch-dashboard.sales-dashboard.stock-opening.index",
                'icon' => 'box',
                'order' => 1,
            ],
            [
                'name' => 'Production Dispatches',
                'slug' => 'kitchen-dispatches',
                'route_name' => "branch-dashboard.sales-dashboard.dispatches.index",
                'icon' => 'truck',
                'order' => 2,
            ],
            [
                'name' => 'Monitor Product Stock',
                'slug' => 'monitor-product-stock',
                'route_name' => "branch-dashboard.sales-dashboard.stock-monitor",
                'icon' => 'eye',
                'order' => 3,
            ],
            [
                'name' => 'My Sales Dashboard',
                'slug' => 'my-sales-dashboard',
                'route_name' => "branch-dashboard.sales-dashboard.my-sales.index",
                'icon' => 'dashboard',
                'order' => 4,
            ],
            [
                'name' => 'Helper',
                'slug' => 'helper',
                'route_name' => "branch-dashboard.sales-dashboard.helper",
                'icon' => 'user',
                'order' => 5,
            ],
            [
                'name' => 'Callbacks',
                'slug' => 'callbacks',
                'route_name' => "branch-dashboard.sales-dashboard.callbacks.index",
                'icon' => 'phone',
                'order' => 6,
            ],
            [
                'name' => 'Product Callbacks',
                'slug' => 'product-callbacks',
                'route_name' => "branch-dashboard.sales-dashboard.callbacks.index",
                'icon' => 'tag',
                'order' => 7,
            ],
            [
                'name' => 'Dispatch Callbacks',
                'slug' => 'dispatch-callbacks',
                'route_name' => "branch-dashboard.sales-dashboard.callbacks.dispatch-callbacks",
                'icon' => 'truck',
                'order' => 8,
            ],
            [
                'name' => 'POS',
                'slug' => 'pos',
                'route_name' => "branch-dashboard.sales-dashboard.pos.index",
                'icon' => 'shopping-cart',
                'order' => 9,
            ],
            [
                'name' => 'My Sales',
                'slug' => 'my-sales',
                'route_name' => 'branch-dashboard.sales-dashboard.my-sales.index',
                'icon' => 'user-circle',
                'order' => 10,
            ],
            [
                'name'=> 'Sales Analytics',
                'slug'=>'sales-analytics',
                'route_name'=> 'branch-dashboard.sales-dashboard.analytics.index',
                'icon'=>'chart-bar',
                'order'=>11
            ],
            [
                'name' => 'Shift Closing',
                'slug' => 'shift-closing',
                'route_name' => 'branch-dashboard.sales-dashboard.shift-closing.index',
                'icon' => 'clock',
                'order' => 12,
            ]
        ];
    }
}

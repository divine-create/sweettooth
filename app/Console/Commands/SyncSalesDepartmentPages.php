<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Department;
use App\Models\DepartmentPage;
use Illuminate\Support\Str;

class SyncSalesDepartmentPages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:sync-pages {--force : Force sync without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all existing Sales departments with the default pages';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('This command will sync all existing Sales departments with the default pages. Do you wish to continue?')) {
                $this->info('Operation cancelled.');
                return;
            }
        }

        // Find all departments that belong to the Sales category
        $salesDepartments = Department::whereHas('category', function ($query) {
            $query->where('name', 'Sales');
        })->get();

        $this->info("Found {$salesDepartments->count()} Sales departments to sync.");

        $progressBar = $this->output->createProgressBar(count($salesDepartments));
        $progressBar->start();

        foreach ($salesDepartments as $department) {
            $this->syncDepartmentPages($department);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('All Sales departments have been synced with default pages.');
    }

    /**
     * Sync a single department with the default sales pages
     */
    protected function syncDepartmentPages(Department $department): void
    {
        $defaultPages = $this->getDefaultSalesPages();

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

    /**
     * Get default sales pages configuration
     */
    protected function getDefaultSalesPages(): array
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

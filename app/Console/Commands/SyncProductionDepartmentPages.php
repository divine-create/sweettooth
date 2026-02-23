<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\DepartmentPage;
use Illuminate\Console\Command;

class SyncProductionDepartmentPages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'departments:sync-production-pages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync default production department pages for existing production departments';

    public function handle(): int
    {
        $pages = [
            [
                'name' => 'Product Assignments',
                'slug' => 'product-assignments',
                'route_name' => 'branch-dashboard.production.product-assignments',
                'icon' => 'clipboard-document-check',
                'order' => 4,
            ],
        ];

        $departments = Department::whereHas('category', function ($q) {
            $q->where('name', 'Production');
        })->get();

        $count = 0;

        foreach ($departments as $department) {
            foreach ($pages as $page) {
                DepartmentPage::updateOrCreate(
                    [
                        'department_id' => $department->id,
                        'slug' => $page['slug'],
                    ],
                    [
                        'name' => $page['name'],
                        'route_name' => $page['route_name'],
                        'icon' => $page['icon'],
                        'order' => $page['order'],
                        'is_active' => true,
                    ]
                );
            }
            $count++;
        }

        $this->info("Synced production pages for {$count} department(s).");

        return self::SUCCESS;
    }
}

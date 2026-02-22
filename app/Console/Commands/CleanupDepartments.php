<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\ProductType;
use App\Models\Recipe;
use App\Models\Product;
use App\Models\Item;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\ProductionRecord;
use App\Models\DailyProduce;
use App\Models\Shift;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupDepartments extends Command
{
    protected $signature = 'departments:cleanup';
    protected $description = 'Remove non-standard departments and their data';

    public function handle(): int
    {
        $this->info("Cleaning up non-standard departments...");

        // Departments to remove (auto-created with wrong names/categories)
        $departmentsToRemove = [
            'HOT KITCHEN',
            'PASTRY',
            'GELATO',
            'TILL',
            'CORNER STORE',
            'CONCESSION',
        ];

        $removed = 0;
        $kept = 0;

        $departments = Department::all();

        foreach ($departments as $dept) {
            if (in_array(strtoupper($dept->name), $departmentsToRemove)) {
                $this->warn("Removing department: {$dept->name} (ID: {$dept->id})");
                
                // Delete associated data
                $this->deleteDepartmentData($dept->id);
                
                $dept->delete();
                $removed++;
            } else {
                $this->info("Keeping department: {$dept->name}");
                $kept++;
            }
        }

        $this->newLine();
        $this->info("Cleanup completed:");
        $this->info("  Removed: {$removed}");
        $this->info("  Kept: {$kept}");

        return 0;
    }

    protected function deleteDepartmentData(int $departmentId): void
    {
        $this->info("  Deleting data for department {$departmentId}...");

        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Delete in order to respect foreign keys
        StockMovement::where('department_id', $departmentId)->delete();
        $this->info("    - Stock movements deleted");

        ProductionRecord::whereHas('dailyProduce', function ($q) use ($departmentId) {
            $q->whereHas('shift', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        })->delete();
        $this->info("    - Production records deleted");

        DailyProduce::whereHas('shift', function ($q) use ($departmentId) {
            $q->where('department_id', $departmentId);
        })->delete();
        $this->info("    - Daily produces deleted");

        Shift::where('department_id', $departmentId)->delete();
        $this->info("    - Shifts deleted");

        Recipe::where('department_id', $departmentId)->delete();
        $this->info("    - Recipes deleted");

        ProductType::where('department_id', $departmentId)->delete();
        $this->info("    - Product types deleted");

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}

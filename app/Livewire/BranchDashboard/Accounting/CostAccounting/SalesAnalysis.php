<?php

namespace App\Livewire\BranchDashboard\Accounting\CostAccounting;

use App\Models\Department;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class SalesAnalysis extends Component
{
    public $dateFilter = 'monthly'; // daily, weekly, monthly
    public $selectedDepartment = 'all';
    public $limit = 10; // Top 10, 20, 30

    public function render()
    {
        $startDate = match ($this->dateFilter) {
            'daily' => Carbon::today(),
            'weekly' => Carbon::now()->startOfWeek(),
            'monthly' => Carbon::now()->startOfMonth(),
            default => Carbon::now()->startOfMonth(),
        };

        // Fetch departments (units)
        $departments = Department::all();

        $salesQuery = SaleItem::with('product')
            ->select('product_id', 
                DB::raw('SUM(quantity) as total_quantity'), 
                DB::raw('SUM(subtotal) as total_revenue'), 
                DB::raw('SUM(line_cost) as total_cogs')
            )
            ->whereHas('sale', function($q) use ($startDate) {
                $q->where('created_at', '>=', $startDate);
                if ($this->selectedDepartment !== 'all') {
                    $q->where('department_id', $this->selectedDepartment);
                }
            })
            ->groupBy('product_id')
            ->orderBy('total_quantity', 'desc')
            ->take($this->limit)
            ->get();

        $topItems = $salesQuery->map(function($saleItem) {
            $grossProfit = $saleItem->total_revenue - $saleItem->total_cogs;
            $margin = $saleItem->total_revenue > 0 
                ? ($grossProfit / $saleItem->total_revenue) * 100 
                : 0;

            return [
                'name' => optional($saleItem->product)->name ?? 'Unknown Product',
                'quantity' => $saleItem->total_quantity,
                'revenue' => $saleItem->total_revenue,
                'cogs' => $saleItem->total_cogs,
                'gross_profit' => $grossProfit,
                'margin' => round($margin, 2),
            ];
        });

        return view('livewire.branch-dashboard.accounting.cost-accounting.sales-analysis', [
            'departments' => $departments,
            'topItems' => $topItems,
        ]);
    }
}

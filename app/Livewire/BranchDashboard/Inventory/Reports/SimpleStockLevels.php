<?php

namespace App\Livewire\BranchDashboard\Inventory\Reports;

use App\Models\Item;
use App\Models\Stock;
use App\Models\DepartmentReport;
use App\Services\Reports\Definitions\InventoryStockLevelsDefinition;
use App\Services\Reports\StockLevelsReportService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Carbon\Carbon;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
#[Title('Simple Stock Levels Report')]
class SimpleStockLevels extends Component
{
    use WithPagination, Interactions;

    public $branchId;
    
    public $searchTerm = '';
    
    public $categoryFilter = '';
    
    public $stockStatusFilter = '';
    
    public $sortBy = 'name';
    
    public $sortDirection = 'asc';
    
    public $periodFilter = 'today';

    public $customDateFrom;

    public $customDateTo;

    public $reportData = null;

    public $summaryMetrics = [];

    public $tablesData = [];

    public $narrative = [];

    public $isLoading = false;

    public $generatedReport = null;

    public $showReportModal = false;

    protected $queryString = [
        'searchTerm' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
        'stockStatusFilter' => ['except' => ''],
        'sortBy' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
        'periodFilter',
        'customDateFrom',
        'customDateTo',
    ];

    public function mount()
    {
        $this->branchId = Auth::guard('web')->user()?->branch_id ?? request()->get('b_id');
        $this->setDateRange();
    }

    public function setDateRange()
    {
        switch ($this->periodFilter) {
            case 'today':
                $this->customDateFrom = Carbon::today()->toDateString();
                $this->customDateTo = Carbon::today()->toDateString();
                break;
            case 'yesterday':
                $this->customDateFrom = Carbon::yesterday()->toDateString();
                $this->customDateTo = Carbon::yesterday()->toDateString();
                break;
            case 'week':
                $this->customDateFrom = Carbon::now()->startOfWeek()->toDateString();
                $this->customDateTo = Carbon::now()->endOfWeek()->toDateString();
                break;
            case 'month':
                $this->customDateFrom = Carbon::now()->startOfMonth()->toDateString();
                $this->customDateTo = Carbon::now()->endOfMonth()->toDateString();
                break;
            case 'last_month':
                $this->customDateFrom = Carbon::now()->subMonth()->startOfMonth()->toDateString();
                $this->customDateTo = Carbon::now()->subMonth()->endOfMonth()->toDateString();
                break;
        }
    }

    public function generatePreview()
    {
        // Validate date range
        $this->validate([
            'customDateFrom' => 'required|date',
            'customDateTo' => 'required|date|after_or_equal:customDateFrom',
        ]);

        $this->isLoading = true;

        try {
            $payload = (new StockLevelsReportService())
                ->useDefinition(new InventoryStockLevelsDefinition())
                ->forBranch($this->branchId)
                ->forPeriod($this->customDateFrom, $this->customDateTo)
                ->getReportData();
            $this->reportData = $payload['report_data'] ?? $payload;
            $this->summaryMetrics = $payload['summary_metrics'] ?? ($this->reportData['summary_metrics'] ?? []);
            $this->tablesData = $payload['tables'] ?? [];
            $this->narrative = $payload['narrative'] ?? [];

            $this->toast()->success('Simple stock levels report generated successfully')->send();
        } catch (\Exception $e) {
            $this->toast()->error('Error: '.$e->getMessage())->send();
        } finally {
            $this->isLoading = false;
        }
    }

    public function generateReport()
    {
        $this->validate([
            'customDateFrom' => 'required|date',
            'customDateTo' => 'required|date|after_or_equal:customDateFrom',
        ]);

        try {
            $this->generatedReport = (new StockLevelsReportService())
                ->useDefinition(new InventoryStockLevelsDefinition())
                ->forBranch($this->branchId)
                ->forPeriod($this->customDateFrom, $this->customDateTo)
                ->generate(auth()->id());

            $this->showReportModal = true;
            $this->toast()->success('Simple stock levels report saved successfully')->send();
        } catch (\Exception $e) {
            $this->toast()->error('Error: '.$e->getMessage())->send();
        }
    }

    public function submitForReview($reportId)
    {
        try {
            $report = DepartmentReport::findOrFail($reportId);
            $report->update(['status' => 'pending_review']);
            $this->toast()->success('Report submitted for review')->send();
            $this->showReportModal = false;
        } catch (\Exception $e) {
            $this->toast()->error('Error: '.$e->getMessage())->send();
        }
    }

    public function updatedPeriodFilter()
    {
        $this->setDateRange();
        if ($this->periodFilter !== 'custom') {
            $this->generatePreview();
        }
    }

    public function updatedSearchTerm()
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter()
    {
        $this->resetPage();
    }

    public function updatedStockStatusFilter()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        
        $this->resetPage();
    }

    public function getStockLevelsProperty()
    {
        $query = Item::query()
            ->where('items.branch_id', $this->branchId)
            ->with(['stocks', 'unitOfMeasure'])
            ->select('items.*');

        // Join with stocks to get available quantity
        $query->leftJoin('stocks', function ($join) {
            $join->on('items.id', '=', 'stocks.item_id')
                 ->where('stocks.branch_id', '=', $this->branchId);
        });

        // Apply search filter
        if ($this->searchTerm) {
            $query->where(function ($q) {
                $q->where('items.name', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('items.sku', 'like', '%' . $this->searchTerm . '%');
            });
        }

        // Apply category filter
        if ($this->categoryFilter) {
            $query->where('items.category', $this->categoryFilter);
        }

        // Apply stock status filter
        if ($this->stockStatusFilter) {
            switch ($this->stockStatusFilter) {
                case 'low_stock':
                    $query->whereColumn('stocks.quantity_available', '<', 'items.reorder_level')
                          ->where('items.reorder_level', '>', 0);
                    break;
                case 'out_of_stock':
                    $query->where('stocks.quantity_available', '<=', 0);
                    break;
                case 'above_reorder':
                    $query->whereColumn('stocks.quantity_available', '>=', 'items.reorder_level');
                    break;
            }
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate(25);
    }

    public function getSummaryStatsProperty()
    {
        $stats = Stock::where('stocks.branch_id', $this->branchId)
            ->join('items', 'stocks.item_id', '=', 'items.id')
            ->selectRaw('
                COUNT(*) as total_items,
                SUM(CASE WHEN stocks.quantity_available <= 0 THEN 1 ELSE 0 END) as out_of_stock,
                SUM(CASE WHEN stocks.quantity_available > 0 AND stocks.quantity_available < items.reorder_level THEN 1 ELSE 0 END) as low_stock,
                SUM(CASE WHEN stocks.quantity_available >= items.reorder_level THEN 1 ELSE 0 END) as adequate_stock,
                SUM(stocks.quantity_available) as total_available
            ')
            ->first();

        return [
            'total_items' => $stats->total_items ?? 0,
            'out_of_stock' => $stats->out_of_stock ?? 0,
            'low_stock' => $stats->low_stock ?? 0,
            'adequate_stock' => $stats->adequate_stock ?? 0,
            'total_available' => $stats->total_available ?? 0,
        ];
    }

    public function render()
    {
        $categories = Item::where('items.branch_id', $this->branchId)
            ->select('category')
            ->distinct()
            ->pluck('category');

        // Show both simple table and report data if available
        $stockLevels = $this->stockLevels;

        return view('livewire.branch-dashboard.inventory.reports.simple-stock-levels', [
            'stockLevels' => $stockLevels,
            'summaryStats' => $this->summaryStats,
            'categories' => $categories,
            'reportData' => $this->reportData,
            'summaryMetrics' => $this->summaryMetrics,
            'tablesData' => $this->tablesData,
            'narrative' => $this->narrative,
            'generatedReport' => $this->generatedReport,
            'showReportModal' => $this->showReportModal,
        ]);
    }
}
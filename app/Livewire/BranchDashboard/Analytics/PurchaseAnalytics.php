<?php

namespace App\Livewire\BranchDashboard\Analytics;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\Reports\AnalyticsSnapshotReportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class PurchaseAnalytics extends Component
{
    use WithPagination;

    public $dateFrom;
    public $dateTo;
    public $supplierFilter = '';
    public $paymentStatus = '';
    public $viewMode = 'overview'; // overview, table, suppliers, items
    public $sortColumn = 'total_spent';
    public $sortDirection = 'desc';
    public ?string $generatedReportId = null;
    public bool $showItemsModal = false;
    public ?Purchase $selectedPurchase = null;
    public array $selectedPurchaseItems = [];
    public array $selectedPurchaseTotals = [];

    protected $queryString = ['dateFrom', 'dateTo', 'supplierFilter', 'paymentStatus'];

    public function mount()
    {
        $this->dateFrom = now()->subDays(90)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->validateDateRange();
        
        // Store branch_id in session for super admin access
        $branchId = $this->getBranchId();
        if ($branchId) {
            session(['branch_id_context' => $branchId]);
        }
    }

    public function updatedDateFrom()
    {
        $this->validateDateRange();
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->validateDateRange();
        $this->resetPage();
    }

    public function updatedSupplierFilter()
    {
        $this->resetPage();
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
        $this->resetPage();
    }

    public function sortByColumn($column)
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'desc';
        }
    }

    public function resetFilters()
    {
        $this->dateFrom = now()->subDays(90)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->supplierFilter = '';
        $this->paymentStatus = '';
        $this->resetPage();
        
        session()->flash('success', 'Filters reset successfully.');
    }

    public function updatedPaymentStatus()
    {
        $this->resetPage();
    }

    public function openPurchaseItems(string $purchaseId): void
    {
        $branchId = $this->getBranchId();

        $purchase = Purchase::with(['purchaseItems.item'])
            ->where('branch_id', $branchId)
            ->findOrFail($purchaseId);

        $items = $purchase->purchaseItems ?? collect();

        $this->selectedPurchase = $purchase;
        $this->selectedPurchaseItems = $items->map(function ($row) {
            return [
                'item_name' => $row->item?->name ?? 'N/A',
                'sku' => $row->item?->sku ?? 'N/A',
                'quantity' => (float) $row->quantity,
                'uom' => $row->uom ?? ($row->item?->uom ?? 'units'),
                'total_cost' => (float) ($row->total_cost ?? 0),
                'cost_per_unit' => (float) ($row->cost_per_unit ?? 0),
            ];
        })->toArray();

        $this->selectedPurchaseTotals = [
            'items_count' => $items->count(),
            'total_qty' => (float) $items->sum('quantity'),
            'total_cost' => (float) $items->sum('total_cost'),
        ];

        $this->showItemsModal = true;
    }

    public function closeItemsModal(): void
    {
        $this->showItemsModal = false;
        $this->selectedPurchase = null;
        $this->selectedPurchaseItems = [];
        $this->selectedPurchaseTotals = [];
    }

    private function validateDateRange()
    {
        $from = \Carbon\Carbon::parse($this->dateFrom);
        $to = \Carbon\Carbon::parse($this->dateTo);
        
        if ($from->greaterThan($to)) {
            // Swap dates
            $temp = $this->dateFrom;
            $this->dateFrom = $this->dateTo;
            $this->dateTo = $temp;
            
            session()->flash('warning', 'Date range was automatically corrected.');
        }
        
        // Warn if range > 1 year
        if ($from->diffInDays($to) > 365) {
            session()->flash('warning', 'Note: Large date ranges may impact performance.');
        }
    }

    private function isAnyFilterActive()
    {
        return $this->supplierFilter || $this->paymentStatus;
    }

    private function getBranchId()
    {
        // Always prioritize URL/query branch context.
        $requestBranchId = request()->get('b_id');
        if ($requestBranchId) {
            session(['branch_id_context' => $requestBranchId]);
            return $requestBranchId;
        }

        // Use persisted context (mainly for super admin navigation continuity).
        if (session()->has('branch_id_context')) {
            return session('branch_id_context');
        }
        
        // Fall back to auth user's branch, then helper.
        return Auth::guard('web')->user()?->branch_id ?? current_branch_id();
    }

    public function getPurchaseTrendData()
    {
        $branchId = $this->getBranchId();

        $purchases = Purchase::where('branch_id', $branchId)
            ->when($this->supplierFilter, fn($q) => $q->where('supplier_name', 'like', '%' . $this->supplierFilter . '%'))
            ->when($this->paymentStatus, fn($q) => $q->where('payment_status', $this->paymentStatus))
            ->whereBetween('purchase_date', [$this->dateFrom, $this->dateTo])
            ->selectRaw('DATE(purchase_date) as date, COUNT(*) as count, SUM(landing_cost) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'categories' => $purchases->map(fn($p) => \Carbon\Carbon::parse($p->date)->format('M d'))->toArray(),
            'series' => [
                ['name' => 'Total Cost (₦)', 'data' => $purchases->pluck('total')->toArray()],
                ['name' => 'Purchase Count', 'data' => $purchases->pluck('count')->toArray(), 'yaxis' => 1],
            ],
        ];
    }

    public function getSupplierAnalysis()
    {
        $branchId = $this->getBranchId();

        return Purchase::where('branch_id', $branchId)
            ->when($this->supplierFilter, fn($q) => $q->where('supplier_name', 'like', '%' . $this->supplierFilter . '%'))
            ->when($this->paymentStatus, fn($q) => $q->where('payment_status', $this->paymentStatus))
            ->whereBetween('purchase_date', [$this->dateFrom, $this->dateTo])
            ->selectRaw('supplier_name, COUNT(*) as purchase_count, SUM(landing_cost) as total_spent')
            ->groupBy('supplier_name')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();
    }

    public function getCostBreakdown()
    {
        $branchId = $this->getBranchId();

        $breakdown = Purchase::where('branch_id', $branchId)
            ->when($this->supplierFilter, fn($q) => $q->where('supplier_name', 'like', '%' . $this->supplierFilter . '%'))
            ->when($this->paymentStatus, fn($q) => $q->where('payment_status', $this->paymentStatus))
            ->whereBetween('purchase_date', [$this->dateFrom, $this->dateTo])
            ->selectRaw('
                SUM(total_fob_ngn) as fob_total,
                SUM(other_costs) as other_costs_total,
                SUM(landing_cost) as landing_total
            ')
            ->first();

        return [
            'labels' => ['FOB Cost', 'Other Costs', 'Landing Cost'],
            'series' => [
                (float) $breakdown->fob_total,
                (float) $breakdown->other_costs_total,
                (float) $breakdown->landing_total,
            ],
        ];
    }

    public function getTopPurchasedItems()
    {
        $branchId = $this->getBranchId();  

        $items = PurchaseItem::select('item_id', DB::raw('SUM(quantity) as total_quantity, SUM(COALESCE(total_cost, 0)) as total_cost'))
            ->whereHas('purchase', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                    ->when($this->supplierFilter, fn($q) => $q->where('supplier_name', 'like', '%' . $this->supplierFilter . '%'))
                    ->when($this->paymentStatus, fn($q) => $q->where('payment_status', $this->paymentStatus))
                    ->whereBetween('purchase_date', [$this->dateFrom, $this->dateTo]);
            })
            ->groupBy('item_id')
            ->orderByDesc('total_cost')
            ->limit(10)
            ->get();

        // Manually load the item relationship to avoid N+1 queries
        $items->load('item');
        
        return $items;
    }

    public function getSummary()
    {
        $branchId = $this->getBranchId();

        $purchases = Purchase::where('branch_id', $branchId)
            ->when($this->supplierFilter, fn($q) => $q->where('supplier_name', 'like', '%' . $this->supplierFilter . '%'))
            ->when($this->paymentStatus, fn($q) => $q->where('payment_status', $this->paymentStatus))
            ->whereBetween('purchase_date', [$this->dateFrom, $this->dateTo])
            ->get();

        $total = $purchases->count();
        $paid = $purchases->where('payment_status', 'paid')->count();
        $partial = $purchases->where('payment_status', 'partial')->count();
        $pending = $purchases->where('payment_status', 'pending')->count();

        return [
            'total_purchases' => $total,
            'total_spent' => $purchases->sum('landing_cost'),
            'avg_purchase_value' => $purchases->avg('landing_cost'),
            'total_items' => PurchaseItem::whereIn('purchase_id', $purchases->pluck('id'))->sum('quantity'),
            'paid_count' => $paid,
            'paid_percentage' => $total > 0 ? round(($paid / $total) * 100, 1) : 0,
            'partial_count' => $partial,
            'partial_percentage' => $total > 0 ? round(($partial / $total) * 100, 1) : 0,
            'pending_count' => $pending,
            'pending_percentage' => $total > 0 ? round(($pending / $total) * 100, 1) : 0,
        ];
    }

    public function generateReport(): void
    {
        $branchId = $this->getBranchId();

        if (!$branchId) {
            session()->flash('warning', 'Branch context is required to generate report.');
            return;
        }

        $from = \Carbon\Carbon::parse($this->dateFrom)->toDateString();
        $to = \Carbon\Carbon::parse($this->dateTo)->toDateString();
        if ($from > $to) {
            session()->flash('warning', 'Invalid date range. Please correct From/To dates.');
            return;
        }

        try {
            $summary = $this->getSummary();
            $trendData = $this->getPurchaseTrendData();
            $supplierAnalysis = $this->getSupplierAnalysis()
                ->map(function ($row) {
                    return [
                        'supplier_name' => $row->supplier_name,
                        'purchase_count' => (int) $row->purchase_count,
                        'total_spent' => (float) $row->total_spent,
                    ];
                })
                ->values()
                ->toArray();
            $costBreakdown = $this->getCostBreakdown();
            $topItems = $this->getTopPurchasedItems()
                ->map(function ($row) {
                    return [
                        'item_name' => $row->item?->name ?? 'N/A',
                        'sku' => $row->item?->sku ?? null,
                        'total_quantity' => (float) $row->total_quantity,
                        'total_cost' => (float) $row->total_cost,
                    ];
                })
                ->values()
                ->toArray();

            $tableRows = $this->buildFilteredPurchasesQuery($branchId)
                ->latest('purchase_date')
                ->limit(300)
                ->get()
                ->map(function ($purchase) {
                    return [
                        'purchase_number' => $purchase->purchase_number,
                        'purchase_date' => optional($purchase->purchase_date)->toDateString(),
                        'supplier_name' => $purchase->supplier_name,
                        'payment_status' => $purchase->payment_status,
                        'landing_cost' => (float) $purchase->landing_cost,
                        'total_fob_ngn' => (float) $purchase->total_fob_ngn,
                        'other_costs' => (float) $purchase->other_costs,
                    ];
                })
                ->values()
                ->toArray();

            $reportData = [
                'source_page' => 'analytics.purchase',
                'generated_at' => now()->toDateTimeString(),
                'filters' => [
                    'date_from' => $from,
                    'date_to' => $to,
                    'supplier_filter' => $this->supplierFilter,
                    'payment_status' => $this->paymentStatus,
                ],
                'summary' => $summary,
                'trend_data' => $trendData,
                'supplier_analysis' => $supplierAnalysis,
                'cost_breakdown' => $costBreakdown,
                'top_items' => $topItems,
                'table_rows' => $tableRows,
            ];

            $report = app(AnalyticsSnapshotReportService::class)->generate([
                'branch_id' => $branchId,
                'report_category' => 'inventory',
                'report_type' => 'purchase_analytics',
                'report_name' => 'Purchase Analytics Report',
                'period_from' => $from,
                'period_to' => $to,
                'report_data' => $reportData,
                'summary_metrics' => [
                    'total_purchases' => (int) ($summary['total_purchases'] ?? 0),
                    'total_spent' => (float) ($summary['total_spent'] ?? 0),
                    'avg_purchase_value' => (float) ($summary['avg_purchase_value'] ?? 0),
                    'paid_percentage' => (float) ($summary['paid_percentage'] ?? 0),
                ],
                'charts_data' => [
                    'trend_data' => $trendData,
                    'cost_breakdown' => $costBreakdown,
                    'supplier_analysis' => $supplierAnalysis,
                ],
                'status' => 'pending_review',
            ]);

            $this->generatedReportId = $report->id;
            session()->flash('success', 'Purchase analytics report generated and submitted for review.');
        } catch (\Throwable $e) {
            report($e);
            session()->flash('warning', 'Failed to generate purchase report. Please try again.');
        }
    }

    private function buildFilteredPurchasesQuery(?string $branchId)
    {
        return Purchase::where('branch_id', $branchId)
            ->when($this->supplierFilter, fn($q) => $q->where('supplier_name', 'like', '%' . $this->supplierFilter . '%'))
            ->when($this->paymentStatus, fn($q) => $q->where('payment_status', $this->paymentStatus))
            ->whereBetween('purchase_date', [$this->dateFrom, $this->dateTo]);
    }

    public function render()
    {
        $branchId = $this->getBranchId();

        $purchases = $this->buildFilteredPurchasesQuery($branchId)
            ->withCount('purchaseItems')
            ->latest('purchase_date')
            ->paginate(15);

        // Manually load recorder for each purchase
        $purchases->getCollection()->transform(function ($purchase) {
            if ($purchase->recorded_by_type && $purchase->recorded_by_id) {
                try {
                    $recordedByClass = $purchase->recorded_by_type;
                    $purchase->recorder = $recordedByClass::find($purchase->recorded_by_id);
                } catch (\Exception $e) {
                    $purchase->recorder = null;
                }
            } else {
                $purchase->recorder = null;
            }
            return $purchase;
        });

        $suppliers = $this->buildFilteredPurchasesQuery($branchId)
            ->distinct()
            ->pluck('supplier_name');

        return view('livewire.branch-dashboard.analytics.purchase-analytics', [
            'purchases' => $purchases,
            'suppliers' => $suppliers,
            'summary' => $this->getSummary(),
            'trendData' => $this->getPurchaseTrendData(),
            'supplierAnalysis' => $this->getSupplierAnalysis(),
            'costBreakdown' => $this->getCostBreakdown(),
            'topItems' => $this->getTopPurchasedItems(),
            'isAnyFilterActive' => $this->isAnyFilterActive(),
        ]);
    }
}

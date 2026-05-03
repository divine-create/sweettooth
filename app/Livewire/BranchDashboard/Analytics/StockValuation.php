<?php

namespace App\Livewire\BranchDashboard\Analytics;

use App\Models\Stock;
use App\Services\Reports\AnalyticsSnapshotReportService;
use App\Traits\Exportable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class StockValuation extends Component
{
    use Exportable, WithPagination;

    public $selectedCategory = '';

    public $searchTerm = '';

    public $sortBy = 'value';

    public $sortDirection = 'desc';

    public $viewMode = 'all';

    public ?string $generatedReportId = null;

    protected $queryString = ['selectedCategory', 'searchTerm', 'sortBy', 'sortDirection', 'viewMode'];

    public function updatedSearchTerm()
    {
        $this->resetPage();
    }

    public function updatedSelectedCategory()
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
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }
    }

    public function getValuationSummary()
    {
        $branchId = Auth::guard('web')->user()?->branch_id ?? request()->get('b_id');

        $stocks = Stock::with('item')->where('branch_id', $branchId)->get();

        $totalAvailableValue = $stocks->sum(fn ($s) => $s->quantity_available * $s->average_cost);
        $totalReservedValue = $stocks->sum(fn ($s) => $s->quantity_reserved * $s->average_cost);
        $totalDamagedValue = $stocks->sum(fn ($s) => $s->quantity_damaged * $s->average_cost);

        return [
            'total_value' => $totalAvailableValue + $totalReservedValue + $totalDamagedValue,
            'available_value' => $totalAvailableValue,
            'reserved_value' => $totalReservedValue,
            'damaged_value' => $totalDamagedValue,
            'total_items' => $stocks->count(),
        ];
    }

    public function getCategoryValuation()
    {
        $branchId = Auth::guard('web')->user()?->branch_id ?? request()->get('b_id');

        $stocks = Stock::with('item')
            ->where('branch_id', $branchId)
            ->when($this->searchTerm, function ($query) {
                $query->whereHas('item', function ($q) {
                    $q->where('name', 'like', '%'.$this->searchTerm.'%')
                        ->orWhere('sku', 'like', '%'.$this->searchTerm.'%');
                });
            })
            ->when($this->selectedCategory, function ($query) {
                $query->whereHas('item', function ($q) {
                    $q->where('category_id', $this->selectedCategory);
                });
            })
            ->get();

        $categoryValues = $stocks->groupBy(fn ($s) => $s->item?->category?->name ?? 'Uncategorized')->map(function ($items) {
            return $items->sum(fn ($s) => ($s->quantity_available + $s->quantity_reserved) * $s->average_cost);
        });

        return [
            'labels' => $categoryValues->keys()->toArray(),
            'series' => $categoryValues->values()->toArray(),
        ];
    }

    public function getTopValueItems()
    {
        $branchId = Auth::guard('web')->user()?->branch_id ?? request()->get('b_id');

        return Stock::with('item')
            ->where('branch_id', $branchId)
            ->when($this->searchTerm, function ($query) {
                $query->whereHas('item', function ($q) {
                    $q->where('name', 'like', '%'.$this->searchTerm.'%')
                        ->orWhere('sku', 'like', '%'.$this->searchTerm.'%');
                });
            })
            ->when($this->selectedCategory, function ($query) {
                $query->whereHas('item', function ($q) {
                    $q->where('category_id', $this->selectedCategory);
                });
            })
            ->get()
            ->map(function ($stock) {
                $stock->total_value = ($stock->quantity_available + $stock->quantity_reserved) * $stock->average_cost;

                return $stock;
            })
            ->sortByDesc('total_value')
            ->take(10);
    }

    public function getLowStockItems()
    {
        $branchId = Auth::guard('web')->user()?->branch_id ?? request()->get('b_id');

        return Stock::with('item')
            ->where('branch_id', $branchId)
            ->when($this->searchTerm, function ($query) {
                $query->whereHas('item', function ($q) {
                    $q->where('name', 'like', '%'.$this->searchTerm.'%')
                        ->orWhere('sku', 'like', '%'.$this->searchTerm.'%');
                });
            })
            ->when($this->selectedCategory, function ($query) {
                $query->whereHas('item', function ($q) {
                    $q->where('category_id', $this->selectedCategory);
                });
            })
            ->where('quantity_available', '<', 100)
            ->get()
            ->map(function ($stock) {
                $stock->total_value = ($stock->quantity_available + $stock->quantity_reserved) * $stock->average_cost;
                $stock->available_value = $stock->quantity_available * $stock->average_cost;

                return $stock;
            })
            ->sortBy([[$this->sortBy, $this->sortDirection === 'desc' ? SORT_DESC : SORT_ASC]])
            ->values();
    }

    public function getCategoryGroupedData()
    {
        $branchId = Auth::guard('web')->user()?->branch_id ?? request()->get('b_id');

        $stocks = Stock::with('item')
            ->where('branch_id', $branchId)
            ->when($this->searchTerm, function ($query) {
                $query->whereHas('item', function ($q) {
                    $q->where('name', 'like', '%'.$this->searchTerm.'%')
                        ->orWhere('sku', 'like', '%'.$this->searchTerm.'%');
                });
            })
            ->when($this->selectedCategory, function ($query) {
                $query->whereHas('item', function ($q) {
                    $q->where('category_id', $this->selectedCategory);
                });
            })
            ->get()
            ->map(function ($stock) {
                $stock->total_value = ($stock->quantity_available + $stock->quantity_reserved) * $stock->average_cost;
                $stock->available_value = $stock->quantity_available * $stock->average_cost;

                return $stock;
            });

        return $stocks->groupBy(fn ($s) => $s->item?->category?->name ?? 'Uncategorized')->map(function ($items) {
            return [
                'category' => $items->first()->item?->category?->name ?? 'Uncategorized',
                'items' => $items,
                'total_value' => $items->sum('total_value'),
                'item_count' => $items->count(),
                'total_qty' => $items->sum(fn ($s) => $s->quantity_available + $s->quantity_reserved),
            ];
        })->sortByDesc('total_value')->values();
    }

    public function generateReport(): void
    {
        $branchId = Auth::guard('web')->user()?->branch_id ?? request()->get('b_id') ?? current_branch_id();
        if (! $branchId) {
            session()->flash('warning', 'Branch context is required to generate report.');

            return;
        }

        try {
            $summary = $this->getValuationSummary();
            $categoryValuation = $this->getCategoryValuation();
            $topItems = $this->getTopValueItems()
                ->map(function ($item) {
                    return [
                        'item_name' => $item->item?->name ?? 'N/A',
                        'sku' => $item->item?->sku ?? null,
                        'category' => $item->item?->category?->name ?? 'Uncategorized',
                        'quantity_available' => (float) $item->quantity_available,
                        'quantity_reserved' => (float) $item->quantity_reserved,
                        'average_cost' => (float) $item->average_cost,
                        'total_value' => (float) $item->total_value,
                    ];
                })
                ->values()
                ->toArray();
            $lowStockItems = $this->getLowStockItems()
                ->map(function ($item) {
                    return [
                        'item_name' => $item->item?->name ?? 'N/A',
                        'sku' => $item->item?->sku ?? null,
                        'category' => $item->item?->category?->name ?? 'Uncategorized',
                        'quantity_available' => (float) $item->quantity_available,
                        'quantity_reserved' => (float) $item->quantity_reserved,
                        'average_cost' => (float) $item->average_cost,
                        'available_value' => (float) $item->available_value,
                        'total_value' => (float) $item->total_value,
                    ];
                })
                ->values()
                ->toArray();
            $categoryData = $this->getCategoryGroupedData()
                ->map(function ($category) {
                    return [
                        'category' => $category['category'],
                        'item_count' => $category['item_count'],
                        'total_qty' => (float) $category['total_qty'],
                        'total_value' => (float) $category['total_value'],
                    ];
                })
                ->values()
                ->toArray();

            $tableRows = Stock::with('item')
                ->where('branch_id', $branchId)
                ->when($this->searchTerm, function ($query) {
                    $query->whereHas('item', function ($q) {
                        $q->where('name', 'like', '%'.$this->searchTerm.'%')
                            ->orWhere('sku', 'like', '%'.$this->searchTerm.'%');
                    });
                })
                ->when($this->selectedCategory, function ($query) {
                    $query->whereHas('item', function ($q) {
                        $q->where('category_id', $this->selectedCategory);
                    });
                })
                ->limit(300)
                ->get()
                ->map(function ($stock) {
                    $totalValue = ($stock->quantity_available + $stock->quantity_reserved) * $stock->average_cost;
                    $availableValue = $stock->quantity_available * $stock->average_cost;

                    return [
                        'item_name' => $stock->item?->name ?? 'N/A',
                        'sku' => $stock->item?->sku ?? null,
                        'category' => $stock->item?->category ?? null,
                        'quantity_available' => (float) $stock->quantity_available,
                        'quantity_reserved' => (float) $stock->quantity_reserved,
                        'average_cost' => (float) $stock->average_cost,
                        'available_value' => (float) $availableValue,
                        'total_value' => (float) $totalValue,
                    ];
                })
                ->values()
                ->toArray();

            $reportData = [
                'source_page' => 'analytics.stock-valuation',
                'generated_at' => now()->toDateTimeString(),
                'filters' => [
                    'selected_category' => $this->selectedCategory,
                    'search_term' => $this->searchTerm,
                    'sort_by' => $this->sortBy,
                    'sort_direction' => $this->sortDirection,
                    'view_mode' => $this->viewMode,
                ],
                'summary' => $summary,
                'category_valuation' => $categoryValuation,
                'top_items' => $topItems,
                'low_stock_items' => $lowStockItems,
                'category_data' => $categoryData,
                'table_rows' => $tableRows,
            ];

            $report = app(AnalyticsSnapshotReportService::class)->generate([
                'branch_id' => $branchId,
                'report_category' => 'inventory',
                'report_type' => 'stock_valuation_analytics',
                'report_name' => 'Stock Valuation Analytics Report',
                'period_from' => now()->toDateString(),
                'period_to' => now()->toDateString(),
                'report_data' => $reportData,
                'summary_metrics' => [
                    'total_value' => (float) ($summary['total_value'] ?? 0),
                    'available_value' => (float) ($summary['available_value'] ?? 0),
                    'reserved_value' => (float) ($summary['reserved_value'] ?? 0),
                    'damaged_value' => (float) ($summary['damaged_value'] ?? 0),
                    'total_items' => (int) ($summary['total_items'] ?? 0),
                ],
                'charts_data' => [
                    'category_valuation' => $categoryValuation,
                    'category_data' => $categoryData,
                ],
                'status' => 'pending_review',
            ]);

            $this->generatedReportId = $report->id;
            session()->flash('success', 'Stock valuation report generated and submitted for review.');
        } catch (\Throwable $e) {
            report($e);
            session()->flash('warning', 'Failed to generate stock valuation report. Please try again.');
        }
    }

    public function exportCSV()
    {
        $branchId = Auth::guard('web')->user()?->branch_id ?? request()->get('b_id');

        $stocks = Stock::with('item')
            ->where('branch_id', $branchId)
            ->when($this->searchTerm, function ($query) {
                $query->whereHas('item', function ($q) {
                    $q->where('name', 'like', '%'.$this->searchTerm.'%')
                        ->orWhere('sku', 'like', '%'.$this->searchTerm.'%');
                });
            })
            ->when($this->selectedCategory, function ($query) {
                $query->whereHas('item', function ($q) {
                    $q->where('category_id', $this->selectedCategory);
                });
            })
            ->get()
            ->map(function ($stock) {
                $stock->total_value = ($stock->quantity_available + $stock->quantity_reserved) * $stock->average_cost;
                $stock->available_value = $stock->quantity_available * $stock->average_cost;

                return $stock;
            });

        $filename = 'stock-valuation-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($stocks) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Item', 'SKU', 'Category', 'Qty Available', 'Qty Reserved', 'Avg Cost', 'Available Value', 'Total Value']);

            foreach ($stocks as $stock) {
                fputcsv($handle, [
                    $stock->item?->name ?? 'N/A',
                    $stock->item?->sku ?? 'N/A',
                    $stock->item?->category?->name ?? 'Uncategorized',
                    number_format($stock->quantity_available, 2),
                    number_format($stock->quantity_reserved, 2),
                    number_format($stock->average_cost, 2),
                    number_format($stock->available_value, 2),
                    number_format($stock->total_value, 2),
                ]);
            }
            fclose($handle);
        }, $filename);
    }

    public function render()
    {
        $branchId = Auth::guard('web')->user()?->branch_id ?? request()->get('b_id');

        $stocks = Stock::with('item')
            ->where('branch_id', $branchId)
            ->when($this->searchTerm, function ($query) {
                $query->whereHas('item', function ($q) {
                    $q->where('name', 'like', '%'.$this->searchTerm.'%')
                        ->orWhere('sku', 'like', '%'.$this->searchTerm.'%');
                });
            })
            ->when($this->selectedCategory, function ($query) {
                $query->whereHas('item', function ($q) {
                    $q->where('category_id', $this->selectedCategory);
                });
            })
            ->get()
            ->map(function ($stock) {
                $stock->total_value = ($stock->quantity_available + $stock->quantity_reserved) * $stock->average_cost;
                $stock->available_value = $stock->quantity_available * $stock->average_cost;

                return $stock;
            })
            ->sortBy([[$this->sortBy, $this->sortDirection === 'desc' ? SORT_DESC : SORT_ASC]])
            ->values();

        $paginatedStocks = new \Illuminate\Pagination\LengthAwarePaginator(
            $stocks->forPage(\Illuminate\Pagination\Paginator::resolveCurrentPage(), 15),
            $stocks->count(),
            15,
            \Illuminate\Pagination\Paginator::resolveCurrentPage(),
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        $categories = \App\Models\ItemCategory::active()
            ->forBranch($branchId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        // Get data based on view mode
        $viewData = match ($this->viewMode) {
            'top' => $this->getTopValueItems(),
            'category' => $this->getCategoryGroupedData(),
            'low' => $this->getLowStockItems(),
            default => $paginatedStocks->items(),
        };

        return view('livewire.branch-dashboard.analytics.stock-valuation', [
            'stocks' => $paginatedStocks,
            'topItems' => $this->getTopValueItems(),
            'lowStockItems' => $this->getLowStockItems(),
            'categoryData' => $this->getCategoryGroupedData(),
            'categories' => $categories,
            'summary' => $this->getValuationSummary(),
            'categoryValuation' => $this->getCategoryValuation(),
        ]);
    }
}

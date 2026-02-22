<?php

namespace App\Livewire\BranchDashboard\Inventory;

use App\Models\Department;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class StockMovements extends Component
{
    use WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public function mount()
    {
        $this->b_id = current_branch_id();
    }

    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->b_id = $branchId;
        $this->resetPage();
    }

    public $quantity = 15;

    public $search = '';

    public $filterType = '';

    public $filterShift = '';

    public $filterDepartment = '';

    public $filterDateFrom = '';

    public $filterDateTo = '';

    // View mode: 'table', 'feed', 'analytics'
    public $viewMode = 'table';

    public function getBranchId()
    {
        return $this->b_id ? $this->b_id : request()->query('b_id');
    }

    /**
     * Get analytics summary for the current filter
     */
    private function getAnalyticsSummary($branchId)
    {
        $baseQuery = StockMovement::query()
            ->whereHas('stock', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });

        // Apply current filters to analytics
        $filteredQuery = clone $baseQuery;
        $this->applyFilters($filteredQuery);

        // Today's movements
        $todayQuery = clone $baseQuery;
        $todayQuery->whereDate('movement_date', today());

        // This week's movements
        $weekQuery = clone $baseQuery;
        $weekQuery->whereBetween('movement_date', [now()->startOfWeek(), now()->endOfWeek()]);

        // Last week's movements for comparison
        $lastWeekQuery = clone $baseQuery;
        $lastWeekQuery->whereBetween('movement_date', [
            now()->subWeek()->startOfWeek(),
            now()->subWeek()->endOfWeek(),
        ]);

        return [
            'today' => [
                'stock_in' => (float) ($todayQuery->clone()->whereIn('type', ['in', 'return'])->sum('quantity') ?? 0),
                'stock_out' => abs((float) ($todayQuery->clone()->whereIn('type', ['out', 'damaged', 'transfer'])->sum('quantity') ?? 0)),
                'transfers' => $todayQuery->clone()->where('type', 'transfer')->count(),
                'total_movements' => $todayQuery->count(),
            ],
            'week' => [
                'stock_in' => (float) ($weekQuery->clone()->whereIn('type', ['in', 'return'])->sum('quantity') ?? 0),
                'stock_out' => abs((float) ($weekQuery->clone()->whereIn('type', ['out', 'damaged', 'transfer'])->sum('quantity') ?? 0)),
                'transfers' => $weekQuery->clone()->where('type', 'transfer')->count(),
                'total_movements' => $weekQuery->count(),
            ],
            'last_week' => [
                'total_movements' => $lastWeekQuery->count(),
            ],
            'filtered' => [
                'total_movements' => $filteredQuery->count(),
                'stock_in' => (float) ($filteredQuery->clone()->whereIn('type', ['in', 'return'])->sum('quantity') ?? 0),
                'stock_out' => abs((float) ($filteredQuery->clone()->whereIn('type', ['out', 'damaged', 'transfer'])->sum('quantity') ?? 0)),
            ],
            'latest_movement' => $filteredQuery->latest('movement_date')->first()?->movement_date,
            'most_moved_item' => $this->getMostMovedItem($branchId),
            'most_active_user' => $this->getMostActiveUser($branchId),
            'peak_hours' => $this->getPeakActivityHours($branchId),
        ];
    }

    /**
     * Apply current filters to a query
     */
    private function applyFilters($query)
    {
        $query->when($this->search, function ($q) {
            $q->where(function ($query) {
                $query->whereHas('stock.item', function ($subQuery) {
                    $subQuery->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('sku', 'like', '%'.$this->search.'%');
                })
                    ->orWhereHas('mover', function ($subQuery) {
                        $subQuery->where('name', 'like', '%'.$this->search.'%');
                    })
                    ->orWhere('notes', 'like', '%'.$this->search.'%');
            });
        })
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->when($this->filterDateFrom, fn ($q) => $q->whereDate('movement_date', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo, fn ($q) => $q->whereDate('movement_date', '<=', $this->filterDateTo))
            ->when($this->filterShift, function ($q) {
                $q->whereHasMorph('reference', ['App\Models\ItemRequest'], function ($subQuery) {
                    $subQuery->where('shift', $this->filterShift);
                });
            })
            ->when($this->filterDepartment, function ($q) {
                $q->whereHasMorph('reference', ['App\Models\ItemRequest'], function ($subQuery) {
                    $subQuery->where('department_id', $this->filterDepartment);
                });
            });
    }

    /**
     * Get the most moved item
     */
    private function getMostMovedItem($branchId)
    {
        $dateRange = $this->getAnalyticsDateRange();

        return StockMovement::query()
            ->select('stock_id', DB::raw('COUNT(*) as movement_count'))
            ->whereHas('stock', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->whereBetween('movement_date', $dateRange)
            ->groupBy('stock_id')
            ->orderByDesc('movement_count')
            ->with('stock.item')
            ->first();
    }

    /**
     * Get the most active user
     */
    private function getMostActiveUser($branchId)
    {
        $dateRange = $this->getAnalyticsDateRange();

        // return StockMovement::query()
        //     ->select('moved_by', DB::raw('COUNT(*) as operation_count'))
        //     ->whereHas('stock', function ($q) use ($branchId) {
        //         $q->where('branch_id', $branchId);
        //     })
        //     ->whereNotNull('moved_by')
        //     ->whereBetween('movement_date', $dateRange)
        //     ->groupBy('moved_by')
        //     ->orderByDesc('operation_count')
        //     ->with('mover')
        //     ->first();

        $topMover = StockMovement::query()
            ->select('moved_by_id', 'moved_by_type', DB::raw('COUNT(*) as operation_count'))
            ->whereBetween('movement_date', $dateRange)
            ->groupBy('moved_by_id', 'moved_by_type')
            ->orderByDesc('operation_count')
            ->with('mover')
            ->first();

        return $topMover?->mover;
    }

    /**
     * Get peak activity hours
     */
    private function getPeakActivityHours($branchId)
    {
        $dateRange = $this->getAnalyticsDateRange();

        $hourlyActivity = StockMovement::query()
            ->select(DB::raw('HOUR(movement_date) as hour'), DB::raw('COUNT(*) as count'))
            ->whereHas('stock', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->whereBetween('movement_date', $dateRange)
            ->groupBy('hour')
            ->orderByDesc('count')
            ->limit(3)
            ->get();

        return $hourlyActivity->map(function ($item) {
            return [
                'hour' => $item->hour,
                'count' => $item->count,
                'formatted' => str_pad($item->hour, 2, '0', STR_PAD_LEFT).':00',
            ];
        });
    }

    /**
     * Get date range for analytics based on filters or default to this week
     */
    private function getAnalyticsDateRange()
    {
        if ($this->filterDateFrom && $this->filterDateTo) {
            return [Carbon::parse($this->filterDateFrom), Carbon::parse($this->filterDateTo)];
        } elseif ($this->filterDateFrom) {
            return [Carbon::parse($this->filterDateFrom), now()];
        } elseif ($this->filterDateTo) {
            return [now()->subWeek(), Carbon::parse($this->filterDateTo)];
        }

        return [now()->startOfWeek(), now()->endOfWeek()];
    }

    /**
     * Get recent activity feed
     */
    private function getActivityFeed($branchId, $limit = 20)
    {
        return StockMovement::with([
            'stock.item',
            'mover',
            'reference',
        ])
            ->whereHas('stock', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->orderBy('movement_date', 'desc')
            ->limit($limit)
            ->get();
    }

    public function render()
    {
        $branchId = $this->getBranchId();

        $query = StockMovement::with([
            'stock.item',
            'stock.branch',
            'mover',
        ])
            ->whereHas('stock', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });

        // Apply filters
        $this->applyFilters($query);

        $query->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc');

        $movements = $query->paginate($this->quantity ?? 15);

        // Get departments for filter
        $departments = Department::orderBy('name')->get();

        // Get analytics summary
        $analytics = $this->getAnalyticsSummary($branchId);

        // Get activity feed (for feed view)
        $activityFeed = $this->viewMode === 'feed' ? $this->getActivityFeed($branchId) : collect();

        return view('livewire.branch-dashboard.inventory.stock-movements', [
            'movements' => $movements,
            'departments' => $departments,
            'analytics' => $analytics,
            'activityFeed' => $activityFeed,
        ]);
    }

    /**
     * Export movements to CSV
     */
    public function exportCsv()
    {
        $branchId = $this->getBranchId();

        $query = StockMovement::with([
            'stock.item',
            'mover',
            'reference',
        ])
            ->whereHas('stock', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });

        $this->applyFilters($query);

        $movements = $query->orderBy('movement_date', 'desc')->get();

        $csvData = [];
        $csvData[] = [
            'Date',
            'Time',
            'Item Name',
            'SKU',
            'Type',
            'Quantity Change',
            'Qty Before',
            'Qty After',
            'UOM',
            'Moved By',
            'Department',
            'Shift',
            'Reference',
            'Notes',
        ];

        foreach ($movements as $movement) {
            $request = ($movement->reference && $movement->reference instanceof \App\Models\ItemRequest)
                ? $movement->reference
                : null;

            $csvData[] = [
                $movement->movement_date->format('Y-m-d'),
                $movement->movement_date->format('H:i:s'),
                $movement->stock->item->name ?? 'N/A',
                $movement->stock->item->sku ?? 'N/A',
                ucfirst($movement->type),
                ($movement->isInbound() ? '+' : '-').number_format(abs($movement->quantity), 2),
                number_format($movement->quantity_before, 2),
                number_format($movement->quantity_after, 2),
                $movement->stock->item->uom ?? '',
                $movement->mover->name ?? 'N/A',
                $request?->department->name ?? 'N/A',
                $request ? ucfirst($request->shift) : 'N/A',
                $request ? $request->request_number : ($movement->reference_id ? '#'.$movement->reference_id : 'N/A'),
                $movement->notes ?? '',
            ];
        }

        $filename = 'stock-movements-'.now()->format('Y-m-d-His').'.csv';
        $handle = fopen('php://temp', 'r+');

        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Switch view mode
     */
    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterType = '';
        $this->filterShift = '';
        $this->filterDepartment = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterType()
    {
        $this->resetPage();
    }

    public function updatedFilterShift()
    {
        $this->resetPage();
    }

    public function updatedFilterDepartment()
    {
        $this->resetPage();
    }

    public function updatedFilterDateFrom()
    {
        $this->resetPage();
    }

    public function updatedFilterDateTo()
    {
        $this->resetPage();
    }
}

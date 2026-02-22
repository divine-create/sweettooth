<?php

namespace App\Livewire\BranchDashboard\Analytics;

use App\Models\ItemRequest;
use App\Models\ItemDispatch;
use App\Models\Department;
use App\Services\Reports\AnalyticsSnapshotReportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

#[Layout('components.layouts.app.branch-dashboard')]
class RequestDispatchAnalytics extends Component
{
    use WithPagination;

    public $dateFrom;
    public $dateTo;
    public $statusFilter = '';
    public $departmentFilter = '';
    public $shiftFilter = '';
    public $searchTerm = '';
    public ?string $generatedReportId = null;

    protected $queryString = [
        'dateFrom',
        'dateTo',
        'statusFilter',
        'departmentFilter',
        'shiftFilter',
    ];

    public function mount()
    {
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    private function resolveBranchId(): ?string
    {
        return request()->get('b_id')
            ?? Auth::guard('web')->user()?->branch_id
            ?? current_branch_id();
    }

    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedDepartmentFilter()
    {
        $this->resetPage();
    }

    public function updatedShiftFilter()
    {
        $this->resetPage();
    }

    public function updatedSearchTerm()
    {
        $this->resetPage();
    }

    /**
     * Get request summary with extended metrics
     */
    public function getRequestSummary()
    {
        $branchId = $this->resolveBranchId();
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();

        $requestsQuery = ItemRequest::where('branch_id', $branchId)
            ->whereBetween('request_date', [$dateFrom, $dateTo])
            ->when($this->departmentFilter, fn($q) => $q->where('department_id', $this->departmentFilter))
            ->when($this->shiftFilter, fn($q) => $q->where('shift', $this->shiftFilter));

        $this->applyStatusFilter($requestsQuery);
        $requests = $requestsQuery->get();

        return [
            'total_requests' => $requests->count(),
            'pending' => $requests->filter(fn($r) => Str::lower((string) $r->status) === 'pending')->count(),
            'approved' => $requests->filter(fn($r) => Str::lower((string) $r->status) === 'approved')->count(),
            'rejected' => $requests->filter(fn($r) => Str::lower((string) $r->status) === 'rejected')->count(),
            'completed' => $requests->filter(fn($r) => Str::lower((string) $r->status) === 'completed')->count(),
            'partially_fulfilled' => $requests->filter(fn($r) => Str::lower((string) $r->status) === 'partially_fulfilled')->count(),
        ];
    }

    /**
     * Get dispatch summary
     */
    public function getDispatchSummary()
    {
        $branchId = $this->resolveBranchId();
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();

        $dispatches = ItemDispatch::where('branch_id', $branchId)
            ->whereBetween('dispatch_time', [$dateFrom, $dateTo])
            ->when($this->departmentFilter, function($q) {
                $q->whereHas('itemRequest', fn($query) => $query->where('department_id', $this->departmentFilter));
            })
            ->get();

        return [
            'total_dispatches' => $dispatches->count(),
            'completed' => $dispatches->where('status', 'completed')->count(),
            'pending' => $dispatches->where('status', 'pending')->count(),
            'failed' => $dispatches->where('status', 'failed')->count(),
            'total_quantity_dispatched' => $dispatches->sum('quantity_dispatched'),
        ];
    }

    /**
     * Get pending requests that need action
     */
    public function getPendingRequests()
    {
        $branchId = $this->resolveBranchId();
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();

        return ItemRequest::with(['department', 'requestedBy', 'requestDetails.item'])
            ->where('branch_id', $branchId)
            ->where('status', 'pending')
            ->whereBetween('request_date', [$dateFrom, $dateTo])
            ->when($this->departmentFilter, fn($q) => $q->where('department_id', $this->departmentFilter))
            ->when($this->shiftFilter, fn($q) => $q->where('shift', $this->shiftFilter))
            ->orderBy('request_date', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($request) {
                $now = now();
                $requestDate = Carbon::parse($request->request_date);
                $waitingTime = $requestDate->diffInHours($now);

                return [
                    'request' => $request,
                    'waiting_time' => $waitingTime,
                    'urgency' => $waitingTime > 48 ? 'Critical' : ($waitingTime > 24 ? 'High' : 'Medium'),
                ];
            });
    }

    /**
     * Get most requested items
     */
    public function getMostRequestedItems()
    {
        $branchId = $this->resolveBranchId();
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();

        $query = DB::table('item_request_details')
            ->join('item_requests', 'item_request_details.request_id', '=', 'item_requests.id')
            ->join('items', 'item_request_details.item_id', '=', 'items.id')
            ->leftJoin('units_of_measure', 'items.uom_id', '=', 'units_of_measure.id')
            ->where('item_requests.branch_id', $branchId)
            ->whereBetween('item_requests.request_date', [$dateFrom, $dateTo])
            ->when($this->departmentFilter, fn($q) => $q->where('item_requests.department_id', $this->departmentFilter))
            ->when($this->shiftFilter, fn($q) => $q->where('item_requests.shift', $this->shiftFilter))
            ->select(
                'items.id',
                'items.name',
                'items.sku',
                'units_of_measure.name as uom',
                DB::raw('COUNT(DISTINCT item_requests.id) as request_count'),
                DB::raw('SUM(item_request_details.quantity_requested) as total_quantity'),
                DB::raw('AVG(item_request_details.quantity_requested) as avg_quantity')
            )
            ->groupBy('items.id', 'items.name', 'items.sku', 'units_of_measure.name')
            ->orderByDesc('request_count')
            ->limit(10);

        $this->applyStatusFilter($query, 'item_requests.status');

        return $query->get();
    }

    /**
     * Get department breakdown
     */
    public function getDepartmentBreakdown()
    {
        $branchId = $this->resolveBranchId();
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();

        $query = ItemRequest::with('department')
            ->where('branch_id', $branchId)
            ->whereBetween('request_date', [$dateFrom, $dateTo])
            ->when($this->shiftFilter, fn($q) => $q->where('shift', $this->shiftFilter))
            ->select('department_id', DB::raw('LOWER(status) as status'), DB::raw('COUNT(*) as count'))
            ->groupBy('department_id', 'status');

        $this->applyStatusFilter($query);

        return $query->get()
            ->groupBy('department_id')
            ->map(function ($group, $deptId) {
                $dept = Department::find($deptId);
                return [
                    'department' => $dept ? $dept->name : 'Unknown',
                    'total' => $group->sum('count'),
                    'pending' => $group->where('status', 'pending')->sum('count'),
                    'approved' => $group->where('status', 'approved')->sum('count'),
                    'rejected' => $group->where('status', 'rejected')->sum('count'),
                    'completed' => $group->where('status', 'completed')->sum('count'),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    /**
     * Get shift breakdown
     */
    public function getShiftBreakdown()
    {
        $branchId = $this->resolveBranchId();
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();

        $query = ItemRequest::where('branch_id', $branchId)
            ->whereBetween('request_date', [$dateFrom, $dateTo])
            ->when($this->departmentFilter, fn($q) => $q->where('department_id', $this->departmentFilter))
            ->select('shift', DB::raw('LOWER(status) as status'), DB::raw('COUNT(*) as count'))
            ->groupBy('shift', 'status');

        $this->applyStatusFilter($query);

        return $query->get()
            ->groupBy('shift')
            ->map(function ($group, $shift) {
                return [
                    'shift' => ucfirst($shift),
                    'total' => $group->sum('count'),
                    'pending' => $group->where('status', 'pending')->sum('count'),
                    'approved' => $group->where('status', 'approved')->sum('count'),
                    'completed' => $group->where('status', 'completed')->sum('count'),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    /**
     * Get approval turnaround time
     */
    public function getApprovalTurnaroundTime()
    {
        $branchId = $this->resolveBranchId();
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();

        $approvedRequestsQuery = ItemRequest::where('branch_id', $branchId)
            ->whereIn('status', ['approved', 'completed'])
            ->whereNotNull('approved_at')
            ->whereBetween('request_date', [$dateFrom, $dateTo])
            ->when($this->departmentFilter, fn($q) => $q->where('department_id', $this->departmentFilter))
            ->when($this->shiftFilter, fn($q) => $q->where('shift', $this->shiftFilter))
            ->select('id', 'request_number', 'request_date', 'approved_at')
            ;

        $this->applyStatusFilter($approvedRequestsQuery);

        $approvedRequests = $approvedRequestsQuery->get()
            ->map(function ($request) {
                $requestDate = Carbon::parse($request->request_date);
                $approvedDate = Carbon::parse($request->approved_at);
                $hours = $requestDate->diffInHours($approvedDate);

                return [
                    'request_number' => $request->request_number,
                    'hours' => $hours,
                    'days' => round($hours / 24, 1),
                ];
            });

        $avgHours = $approvedRequests->avg('hours');

        return [
            'average_hours' => round($avgHours, 1),
            'average_days' => round($avgHours / 24, 1),
            'fastest' => $approvedRequests->sortBy('hours')->first(),
            'slowest' => $approvedRequests->sortByDesc('hours')->first(),
            'details' => $approvedRequests->sortByDesc('hours')->take(10),
        ];
    }

    /**
     * Get fulfillment rate
     */
    public function getFulfillmentRate()
    {
        $branchId = $this->resolveBranchId();
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();

        $requestsQuery = ItemRequest::where('branch_id', $branchId)
            ->whereBetween('request_date', [$dateFrom, $dateTo])
            ->when($this->departmentFilter, fn($q) => $q->where('department_id', $this->departmentFilter))
            ->when($this->shiftFilter, fn($q) => $q->where('shift', $this->shiftFilter));

        $this->applyStatusFilter($requestsQuery);
        $requests = $requestsQuery->get();

        $total = $requests->count();
        $completed = $requests->where('status', 'completed')->count();
        $rate = $total > 0 ? ($completed / $total) * 100 : 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'rate' => round($rate, 1),
        ];
    }

    /**
     * Get daily trend
     */
    public function getDailyTrend()
    {
        $branchId = $this->resolveBranchId();
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();

        $query = ItemRequest::where('branch_id', $branchId)
            ->whereBetween('request_date', [$dateFrom, $dateTo])
            ->when($this->departmentFilter, fn($q) => $q->where('department_id', $this->departmentFilter))
            ->when($this->shiftFilter, fn($q) => $q->where('shift', $this->shiftFilter))
            ->select(
                DB::raw('DATE(request_date) as date'),
                'status',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date', 'status')
            ->orderBy('date', 'desc')
            ->limit(84);

        $this->applyStatusFilter($query);

        return $query->get()
            ->groupBy('date')
            ->map(function ($group, $date) {
                return [
                    'date' => Carbon::parse($date)->format('M d'),
                    'total' => $group->sum('count'),
                    'pending' => $group->where('status', 'pending')->sum('count'),
                    'approved' => $group->where('status', 'approved')->sum('count'),
                    'completed' => $group->where('status', 'completed')->sum('count'),
                    'rejected' => $group->where('status', 'rejected')->sum('count'),
                ];
            })
            ->reverse()
            ->take(14)
            ->values();
    }

    /**
     * Get period comparison
     */
    public function getPeriodComparison()
    {
        $branchId = $this->resolveBranchId();
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();
        $daysDiff = $dateFrom->diffInDays($dateTo);

        // Current period
        $currentQuery = ItemRequest::where('branch_id', $branchId)
            ->whereBetween('request_date', [$dateFrom, $dateTo])
            ->when($this->departmentFilter, fn($q) => $q->where('department_id', $this->departmentFilter))
            ->when($this->shiftFilter, fn($q) => $q->where('shift', $this->shiftFilter));

        $this->applyStatusFilter($currentQuery);
        $currentRequests = $currentQuery->count();

        // Previous period
        $previousQuery = ItemRequest::where('branch_id', $branchId)
            ->whereBetween('request_date', [
                $dateFrom->copy()->subDays($daysDiff)->startOfDay(),
                $dateFrom->copy()->subDay()->endOfDay()
            ])
            ->when($this->departmentFilter, fn($q) => $q->where('department_id', $this->departmentFilter))
            ->when($this->shiftFilter, fn($q) => $q->where('shift', $this->shiftFilter));

        $this->applyStatusFilter($previousQuery);
        $previousRequests = $previousQuery->count();

        $change = $previousRequests > 0 ? (($currentRequests - $previousRequests) / $previousRequests) * 100 : 0;

        return [
            'current' => $currentRequests,
            'previous' => $previousRequests,
            'change' => round($change, 1),
        ];
    }

    /**
     * Generate smart insights
     */
    public function getSmartInsights()
    {
        $summary = $this->getRequestSummary();
        $fulfillmentRate = $this->getFulfillmentRate();
        $insights = [];

        // Pending requests insight
        if ($summary['pending'] > 0) {
            $insights[] = [
                'type' => 'warning',
                'icon' => '⚠️',
                'message' => "You have {$summary['pending']} pending requests waiting for approval."
            ];
        }

        // High fulfillment rate
        if ($fulfillmentRate['rate'] >= 80) {
            $insights[] = [
                'type' => 'success',
                'icon' => '✅',
                'message' => "Great job! {$fulfillmentRate['rate']}% fulfillment rate shows excellent request processing."
            ];
        }

        // Low fulfillment rate
        if ($fulfillmentRate['rate'] < 50 && $summary['total_requests'] > 0) {
            $insights[] = [
                'type' => 'critical',
                'icon' => '🔴',
                'message' => "Fulfillment rate is low at {$fulfillmentRate['rate']}%. Review pending and rejected requests."
            ];
        }

        // Rejected requests
        if ($summary['rejected'] > 0) {
            $percentage = round(($summary['rejected'] / $summary['total_requests']) * 100, 1);
            $insights[] = [
                'type' => 'warning',
                'icon' => '❌',
                'message' => "{$summary['rejected']} requests ({$percentage}%) were rejected. Review rejection reasons."
            ];
        }

        return $insights;
    }

    public function generateReport(): void
    {
        $branchId = $this->resolveBranchId();
        if (!$branchId) {
            session()->flash('warning', 'Branch context is required to generate report.');
            return;
        }

        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();

        if ($dateFrom->greaterThan($dateTo)) {
            session()->flash('warning', 'Invalid date range. "From" date must be before "To".');
            return;
        }

        try {
            $requestSummary = $this->getRequestSummary();
            $dispatchSummary = $this->getDispatchSummary();
            $pendingRequests = $this->getPendingRequests()
                ->map(function ($row) {
                    return [
                        'request_number' => $row['request']->request_number,
                        'department' => $row['request']->department?->name,
                        'shift' => $row['request']->shift,
                        'waiting_time_hours' => (int) $row['waiting_time'],
                        'urgency' => $row['urgency'],
                    ];
                })
                ->values()
                ->toArray();
            $mostRequestedItems = $this->getMostRequestedItems()->map(function ($item) {
                return [
                    'item_name' => $item->name,
                    'sku' => $item->sku,
                    'request_count' => (int) $item->request_count,
                    'total_quantity' => (float) $item->total_quantity,
                    'uom' => $item->uom,
                ];
            })->values()->toArray();
            $departmentBreakdown = $this->getDepartmentBreakdown()->values()->toArray();
            $shiftBreakdown = $this->getShiftBreakdown()->values()->toArray();
            $approvalTurnaround = $this->getApprovalTurnaroundTime();
            $fulfillmentRate = $this->getFulfillmentRate();
            $dailyTrend = $this->getDailyTrend()->values()->toArray();
            $periodComparison = $this->getPeriodComparison();
            $smartInsights = $this->getSmartInsights();

            $requestRows = ItemRequest::with(['department', 'requestedBy', 'approver', 'requestDetails'])
                ->where('branch_id', $branchId)
                ->whereBetween('request_date', [$dateFrom, $dateTo])
                ->when($this->departmentFilter, fn($q) => $q->where('department_id', $this->departmentFilter))
                ->when($this->shiftFilter, fn($q) => $q->where('shift', $this->shiftFilter))
                ->when($this->searchTerm, function ($q) {
                    $q->where(function ($query) {
                        $query->where('request_number', 'like', '%' . $this->searchTerm . '%')
                            ->orWhereHas('requestedBy', function ($subQuery) {
                                $subQuery->where('name', 'like', '%' . $this->searchTerm . '%');
                            })
                            ->orWhereHas('department', function ($subQuery) {
                                $subQuery->where('name', 'like', '%' . $this->searchTerm . '%');
                            });
                    });
                })
                ;

            $this->applyStatusFilter($requestRows);

            $requestRows = $requestRows->latest('request_date')
                ->limit(300)
                ->get()
                ->map(function ($request) {
                    return [
                        'request_number' => $request->request_number,
                        'request_date' => optional($request->request_date)->toDateTimeString(),
                        'department' => $request->department?->name,
                        'shift' => $request->shift,
                        'status' => $request->status,
                        'requested_by' => $request->requestedBy?->name,
                        'approved_by' => $request->approver?->name,
                        'items_count' => $request->requestDetails->count(),
                    ];
                })
                ->values()
                ->toArray();

            $reportData = [
                'source_page' => 'analytics.request-dispatch',
                'generated_at' => now()->toDateTimeString(),
                'filters' => [
                    'date_from' => $dateFrom->toDateString(),
                    'date_to' => $dateTo->toDateString(),
                    'status' => $this->statusFilter,
                    'department_id' => $this->departmentFilter,
                    'shift' => $this->shiftFilter,
                    'search_term' => $this->searchTerm,
                ],
                'request_summary' => $requestSummary,
                'dispatch_summary' => $dispatchSummary,
                'pending_requests' => $pendingRequests,
                'most_requested_items' => $mostRequestedItems,
                'department_breakdown' => $departmentBreakdown,
                'shift_breakdown' => $shiftBreakdown,
                'approval_turnaround' => $approvalTurnaround,
                'fulfillment_rate' => $fulfillmentRate,
                'daily_trend' => $dailyTrend,
                'period_comparison' => $periodComparison,
                'smart_insights' => $smartInsights,
                'table_rows' => $requestRows,
            ];

            $report = app(AnalyticsSnapshotReportService::class)->generate([
                'branch_id' => $branchId,
                'department_id' => $this->departmentFilter ?: null,
                'report_category' => 'inventory',
                'report_type' => 'request_dispatch_analytics',
                'report_name' => 'Request & Dispatch Analytics Report',
                'period_from' => $dateFrom->toDateString(),
                'period_to' => $dateTo->toDateString(),
                'report_data' => $reportData,
                'summary_metrics' => [
                    'total_requests' => (int) ($requestSummary['total_requests'] ?? 0),
                    'pending_requests' => (int) ($requestSummary['pending'] ?? 0),
                    'completed_requests' => (int) ($requestSummary['completed'] ?? 0),
                    'total_dispatches' => (int) ($dispatchSummary['total_dispatches'] ?? 0),
                    'fulfillment_rate' => (float) ($fulfillmentRate['rate'] ?? 0),
                ],
                'charts_data' => [
                    'daily_trend' => $dailyTrend,
                    'department_breakdown' => $departmentBreakdown,
                    'shift_breakdown' => $shiftBreakdown,
                ],
                'status' => 'pending_review',
            ]);

            $this->generatedReportId = $report->id;
            session()->flash('success', 'Request/dispatch analytics report generated and submitted for review.');
        } catch (\Throwable $e) {
            report($e);
            session()->flash('warning', 'Failed to generate request/dispatch report. Please try again.');
        }
    }

    public function render()
    {
        $branchId = $this->resolveBranchId();
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();

        // Main requests table with all filters applied
        $requestsQuery = ItemRequest::with(['department', 'requestedBy', 'approver', 'requestDetails'])
            ->where('branch_id', $branchId)
            ->whereBetween('request_date', [$dateFrom, $dateTo])
            ->when($this->departmentFilter, fn($q) => $q->where('department_id', $this->departmentFilter))
            ->when($this->shiftFilter, fn($q) => $q->where('shift', $this->shiftFilter))
            ->when($this->searchTerm, function($q) {
                $q->where(function($query) {
                    $query->where('request_number', 'like', '%' . $this->searchTerm . '%')
                        ->orWhereHas('requestedBy', function($subQuery) {
                            $subQuery->where('name', 'like', '%' . $this->searchTerm . '%');
                        })
                        ->orWhereHas('department', function($subQuery) {
                            $subQuery->where('name', 'like', '%' . $this->searchTerm . '%');
                        });
                });
            })
            ;

        $this->applyStatusFilter($requestsQuery);
        $requests = $requestsQuery->latest('request_date')->paginate(15);

        $departments = Department::orderBy('name')->get();
        $statuses = ['pending', 'approved', 'rejected', 'completed', 'partially_fulfilled'];
        $shifts = ['morning', 'afternoon', 'night'];

        return view('livewire.branch-dashboard.analytics.request-dispatch-analytics', [
            'requests' => $requests,
            'departments' => $departments,
            'statuses' => $statuses,
            'shifts' => $shifts,
            'requestSummary' => $this->getRequestSummary(),
            'dispatchSummary' => $this->getDispatchSummary(),
            'pendingRequests' => $this->getPendingRequests(),
            'mostRequestedItems' => $this->getMostRequestedItems(),
            'departmentBreakdown' => $this->getDepartmentBreakdown(),
            'shiftBreakdown' => $this->getShiftBreakdown(),
            'approvalTurnaround' => $this->getApprovalTurnaroundTime(),
            'fulfillmentRate' => $this->getFulfillmentRate(),
            'dailyTrend' => $this->getDailyTrend(),
            'periodComparison' => $this->getPeriodComparison(),
            'smartInsights' => $this->getSmartInsights(),
        ]);
    }

    private function statusFilterValues(): ?array
    {
        $status = trim((string) $this->statusFilter);
        if ($status === '') {
            return null;
        }

        $status = Str::lower($status);
        if ($status === 'approved') {
            return ['approved', 'completed'];
        }

        return [$status];
    }

    private function applyStatusFilter($query, string $column = 'status'): void
    {
        $values = $this->statusFilterValues();
        if (! $values) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $query->whereRaw("LOWER({$column}) in ({$placeholders})", $values);
    }
}

<?php

namespace App\Livewire\BranchDashboard\Analytics;

use App\Models\ItemRequest;
use App\Models\Stock;
use App\Services\Reports\AnalyticsSnapshotReportService;
use App\Traits\Exportable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class AlertsDashboard extends Component
{
    use Exportable;

    public $alertType = '';

    public ?string $generatedReportId = null;

    #[Url(keep: true)]
    public ?string $b_id = null;

    // Listen for branch changes from BranchSelector (for super admins)
    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->b_id = $branchId;
    }

    public function getAllAlerts()
    {
        $branchId = $this->b_id ?? request()->get('b_id');
        $stocks = Stock::with('item')->where('branch_id', $branchId)->get();
        $alerts = collect();

        // Critical and Expired Items
        foreach ($stocks as $stock) {
            if (! $stock->item) {
                continue;
            }
            if ($stock->health_status === 'expired') {
                $alerts->push([
                    'type' => 'critical',
                    'category' => 'expired',
                    'priority' => 1,
                    'message' => "{$stock->item->name} has expired",
                    'item' => $stock->item->name,
                    'sku' => $stock->item->sku,
                    'date' => $stock->expiry_date,
                    'action' => 'Remove from inventory immediately',
                ]);
            } elseif ($stock->health_status === 'critical') {
                $alerts->push([
                    'type' => 'critical',
                    'category' => 'health',
                    'priority' => 2,
                    'message' => "{$stock->item->name} is in critical condition",
                    'item' => $stock->item->name,
                    'sku' => $stock->item->sku,
                    'action' => 'Inspect and take appropriate action',
                ]);
            }
        }

        // Low Stock Alerts
        foreach ($stocks as $stock) {
            if (! $stock->item) {
                continue;
            }
            if ($stock->quantity_available < ($stock->item->reorder_level ?? 0) && $stock->quantity_available > 0) {
                $alerts->push([
                    'type' => 'warning',
                    'category' => 'low_stock',
                    'priority' => 3,
                    'message' => "{$stock->item->name} is below reorder level",
                    'item' => $stock->item->name,
                    'sku' => $stock->item->sku,
                    'current' => $stock->quantity_available,
                    'reorder_level' => $stock->item->reorder_level,
                    'action' => 'Reorder stock',
                ]);
            } elseif ($stock->quantity_available <= 0) {
                $alerts->push([
                    'type' => 'critical',
                    'category' => 'out_of_stock',
                    'priority' => 2,
                    'message' => "{$stock->item->name} is out of stock",
                    'item' => $stock->item->name,
                    'sku' => $stock->item->sku,
                    'action' => 'Urgent reorder required',
                ]);
            }
        }

        // Expiring Soon (within 30 days)
        foreach ($stocks as $stock) {
            if (! $stock->item) {
                continue;
            }
            if ($stock->expiry_date && \Carbon\Carbon::parse($stock->expiry_date)->lte(now()->addDays(30)) && \Carbon\Carbon::parse($stock->expiry_date)->gt(now())) {
                $alerts->push([
                    'type' => 'warning',
                    'category' => 'expiring_soon',
                    'priority' => 3,
                    'message' => "{$stock->item->name} will expire soon",
                    'item' => $stock->item->name,
                    'sku' => $stock->item->sku,
                    'date' => $stock->expiry_date,
                    'days_left' => \Carbon\Carbon::parse($stock->expiry_date)->diffInDays(now()),
                    'action' => 'Use or dispose before expiry',
                ]);
            }
        }

        // High Damaged Stock
        foreach ($stocks as $stock) {
            if (! $stock->item) {
                continue;
            }
            if ($stock->quantity_damaged > 0) {
                $percentage = ($stock->quantity_damaged / ($stock->quantity_available + $stock->quantity_damaged + $stock->quantity_reserved)) * 100;
                if ($percentage > 10) {
                    $alerts->push([
                        'type' => 'warning',
                        'category' => 'damaged',
                        'priority' => 4,
                        'message' => "{$stock->item->name} has high damaged stock",
                        'item' => $stock->item->name,
                        'sku' => $stock->item->sku,
                        'damaged_qty' => $stock->quantity_damaged,
                        'percentage' => round($percentage, 1),
                        'action' => 'Investigate and address quality issues',
                    ]);
                }
            }
        }

        // Pending Requests
        $pendingRequests = ItemRequest::where('branch_id', $branchId)
            ->where('status', 'pending')
            ->where('request_date', '<', now()->subDays(2))
            ->count();

        if ($pendingRequests > 0) {
            $alerts->push([
                'type' => 'info',
                'category' => 'pending_requests',
                'priority' => 5,
                'message' => "{$pendingRequests} requests pending for more than 2 days",
                'count' => $pendingRequests,
                'action' => 'Review and process pending requests',
            ]);
        }

        return $alerts->sortBy('priority')->when($this->alertType, function ($collection) {
            return $collection->where('category', $this->alertType);
        });
    }

    public function getAlertSummary()
    {
        $alerts = $this->getAllAlerts();

        return [
            'total' => $alerts->count(),
            'critical' => $alerts->where('type', 'critical')->count(),
            'warning' => $alerts->where('type', 'warning')->count(),
            'info' => $alerts->where('type', 'info')->count(),
            'expired' => $alerts->where('category', 'expired')->count(),
            'low_stock' => $alerts->where('category', 'low_stock')->count(),
            'out_of_stock' => $alerts->where('category', 'out_of_stock')->count(),
        ];
    }

    public function exportCSV()
    {
        $alerts = $this->getAllAlerts();
        $filename = 'alerts-dashboard-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($alerts) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Type', 'Category', 'Message', 'Item', 'SKU', 'Current', 'Reorder Level', 'Days Left', 'Damaged Qty', 'Recommended Action']);

            foreach ($alerts as $alert) {
                fputcsv($handle, [
                    ucfirst($alert['type']),
                    ucfirst(str_replace('_', ' ', $alert['category'])),
                    $alert['message'],
                    $alert['item'] ?? '',
                    $alert['sku'] ?? '',
                    isset($alert['current']) ? number_format($alert['current'], 2) : '',
                    isset($alert['reorder_level']) ? number_format($alert['reorder_level'], 2) : '',
                    $alert['days_left'] ?? '',
                    isset($alert['damaged_qty']) ? number_format($alert['damaged_qty'], 2) : '',
                    $alert['action'] ?? '',
                ]);
            }
            fclose($handle);
        }, $filename);
    }

    public function generateReport(): void
    {
        $branchId = $this->b_id ?? request()->get('b_id') ?? Auth::guard('web')->user()?->branch_id ?? current_branch_id();
        if (! $branchId) {
            session()->flash('warning', 'Branch context is required to generate report.');

            return;
        }

        try {
            $alerts = $this->getAllAlerts()->values();
            $summary = $this->getAlertSummary();

            $alertsByType = $alerts->groupBy('type')->map->count()->toArray();
            $alertsByCategory = $alerts->groupBy('category')->map->count()->toArray();

            $reportData = [
                'source_page' => 'analytics.alerts',
                'generated_at' => now()->toDateTimeString(),
                'filters' => [
                    'alert_type' => $this->alertType,
                ],
                'summary' => $summary,
                'alerts_by_type' => $alertsByType,
                'alerts_by_category' => $alertsByCategory,
                'table_rows' => $alerts->map(function ($alert) {
                    return [
                        'type' => $alert['type'] ?? null,
                        'category' => $alert['category'] ?? null,
                        'message' => $alert['message'] ?? null,
                        'item' => $alert['item'] ?? null,
                        'sku' => $alert['sku'] ?? null,
                        'current' => $alert['current'] ?? null,
                        'reorder_level' => $alert['reorder_level'] ?? null,
                        'days_left' => $alert['days_left'] ?? null,
                        'damaged_qty' => $alert['damaged_qty'] ?? null,
                        'count' => $alert['count'] ?? null,
                        'priority' => $alert['priority'] ?? null,
                        'action' => $alert['action'] ?? null,
                    ];
                })->values()->toArray(),
            ];

            $report = app(AnalyticsSnapshotReportService::class)->generate([
                'branch_id' => $branchId,
                'report_category' => 'inventory',
                'report_type' => 'alerts_analytics',
                'report_name' => 'Alerts Analytics Report',
                'period_from' => now()->toDateString(),
                'period_to' => now()->toDateString(),
                'report_data' => $reportData,
                'summary_metrics' => [
                    'total_alerts' => (int) ($summary['total'] ?? 0),
                    'critical_alerts' => (int) ($summary['critical'] ?? 0),
                    'warning_alerts' => (int) ($summary['warning'] ?? 0),
                    'info_alerts' => (int) ($summary['info'] ?? 0),
                    'low_stock_alerts' => (int) ($summary['low_stock'] ?? 0),
                    'out_of_stock_alerts' => (int) ($summary['out_of_stock'] ?? 0),
                ],
                'charts_data' => [
                    'alerts_by_type' => $alertsByType,
                    'alerts_by_category' => $alertsByCategory,
                ],
                'status' => 'pending_review',
            ]);

            $this->generatedReportId = $report->id;
            session()->flash('success', 'Alerts report generated and submitted for review.');
        } catch (\Throwable $e) {
            report($e);
            session()->flash('warning', 'Failed to generate alerts report. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.branch-dashboard.analytics.alerts-dashboard', [
            'alerts' => $this->getAllAlerts(),
            'summary' => $this->getAlertSummary(),
        ]);
    }
}

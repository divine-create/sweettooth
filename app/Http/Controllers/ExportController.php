<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\HealthCheck;
use App\Models\ItemRequest;
use App\Models\DepartmentReport;
use App\Traits\Exportable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExportController extends Controller
{
    use Exportable;

    /**
     * Export stock level analytics
     */
    public function stockLevelAnalytics(Request $request)
    {
        $branchId = Auth::guard('web')->user()?->branch_id ?? $request->get('b_id');
        
        $stocks = Stock::with(['item'])
            ->where('branch_id', $branchId)
            ->when($request->get('search'), function ($query) use ($request) {
                $query->whereHas('item', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->get('search') . '%')
                      ->orWhere('sku', 'like', '%' . $request->get('search') . '%');
                });
            })
            ->when($request->get('category'), function ($query) use ($request) {
                $query->whereHas('item', function ($q) use ($request) {
                    $q->where('category', $request->get('category'));
                });
            })
            ->when($request->get('health'), function ($query) use ($request) {
                $query->where('health_status', $request->get('health'));
            })
            ->get();

        if ($stocks->isEmpty()) {
            return back()->with('warning', 'No stock data to export.');
        }

        return $this->exportAsCSV($stocks);
    }

    /**
     * Export stocks as CSV
     */
    private function exportAsCSV($stocks)
    {
        $filename = 'stock-level-analytics-' . now()->format('Y-m-d-His') . '.csv';
        
        return response()->streamDownload(function () use ($stocks) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Item', 'SKU', 'Category', 'Available', 'Reserved', 'Damaged', 'Reorder Level', 'Health Status']);

            foreach ($stocks as $stock) {
                fputcsv($handle, [
                    $stock->item->name,
                    $stock->item->sku,
                    str_replace('_', ' ', ucfirst($stock->item->category)),
                    number_format($stock->quantity_available, 2),
                    number_format($stock->quantity_reserved, 2),
                    number_format($stock->quantity_damaged, 2),
                    $stock->item->reorder_level ? number_format($stock->item->reorder_level, 2) : 'Not set',
                    ucfirst($stock->health_status),
                ]);
            }
            fclose($handle);
        }, $filename);
    }

    /**
     * Export health checks
     */
    public function healthChecks(Request $request)
    {
        $branchId = Auth::guard('web')->user()?->branch_id ?? $request->get('b_id');
        
        $healthChecks = HealthCheck::with(['stock.item', 'stock.branch', 'checker'])
            ->whereHas('stock', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->when($request->get('search'), function ($q) use ($request) {
                $q->whereHas('stock.item', function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->get('search') . '%')
                        ->orWhere('sku', 'like', '%' . $request->get('search') . '%');
                });
            })
            ->when($request->get('condition'), fn($q) => $q->where('condition', $request->get('condition')))
            ->orderBy('check_date', 'desc')
            ->get();

        if ($healthChecks->isEmpty()) {
            return back()->with('warning', 'No health checks to export.');
        }

        return $this->export(
            'health-checks-' . now()->format('Y-m-d'),
            $healthChecks,
            'exports.inventory.health-checks'
        );
    }

    /**
     * Export item requests
     */
    public function itemRequests(Request $request)
    {
        $branchId = Auth::guard('web')->user()?->branch_id ?? $request->get('b_id');
        
        $requests = ItemRequest::with(['branch', 'department', 'requester', 'requestDetails'])
            ->where('branch_id', $branchId)
            ->when($request->get('search'), function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('request_number', 'like', '%' . $request->get('search') . '%')
                        ->orWhereHas('requester', function ($subQuery) use ($request) {
                            $subQuery->where('name', 'like', '%' . $request->get('search') . '%');
                        })
                        ->orWhereHas('department', function ($subQuery) use ($request) {
                            $subQuery->where('name', 'like', '%' . $request->get('search') . '%');
                        });
                });
            })
            ->when($request->get('department'), fn($q) => $q->where('department_id', $request->get('department')))
            ->when($request->get('status'), fn($q) => $q->where('status', $request->get('status')))
            ->orderBy('created_at', 'desc')
            ->get();

        if ($requests->isEmpty()) {
            return back()->with('warning', 'No requests to export.');
        }

        return $this->export(
            'item-requests-' . now()->format('Y-m-d'),
            $requests,
            'exports.inventory.item-requests'
        );
    }

    /**
     * Export a saved department report as CSV.
     */
    public function departmentReport(Request $request, string $reportId)
    {
        $user = Auth::guard('web')->user();
        if (! $user || ! $this->canExportReports($user)) {
            abort(403, 'You do not have permission to export reports.');
        }

        $branchId = $user->branch_id ?? $request->get('b_id') ?? current_branch_id();
        $report = DepartmentReport::query()
            ->with('department')
            ->where('branch_id', $branchId)
            ->findOrFail($reportId);

        $filename = sprintf(
            'report-%s-%s-%s',
            $report->report_category,
            $report->report_type,
            now()->format('Y-m-d')
        );

        return $this->exportDepartmentReportCsv($report, $filename);
    }

    private function exportDepartmentReportCsv(DepartmentReport $report, string $filename)
    {
        $payload = $report->report_data ?? [];
        $summary = $report->summary_metrics ?? ($payload['summary_metrics'] ?? []);
        $tables = $payload['tables'] ?? [];
        $narrative = $payload['narrative'] ?? [];
        $periodInfo = $payload['period_info'] ?? [
            'from' => $report->period_from,
            'to' => $report->period_to,
        ];

        $csvFilename = $filename . '.csv';

        return response()->streamDownload(function () use ($report, $summary, $tables, $narrative, $periodInfo) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Report Overview']);
            fputcsv($handle, ['Report Name', $report->report_name]);
            fputcsv($handle, ['Category', $report->report_category]);
            fputcsv($handle, ['Type', $report->report_type]);
            fputcsv($handle, ['Period', ($periodInfo['from'] ?? '-') . ' to ' . ($periodInfo['to'] ?? '-')]);
            fputcsv($handle, ['Generated On', optional($report->report_date)->format('Y-m-d') ?? '-']);
            fputcsv($handle, []);

            fputcsv($handle, ['Summary Metrics']);
            if (! empty($summary)) {
                foreach ($summary as $label => $value) {
                    $labelText = \Illuminate\Support\Str::of($label)->replace('_', ' ')->title();
                    $valueText = is_numeric($value) ? number_format($value, 2) : (is_array($value) ? json_encode($value) : $value);
                    fputcsv($handle, [$labelText, $valueText]);
                }
            } else {
                fputcsv($handle, ['No summary metrics available.']);
            }
            fputcsv($handle, []);

            if (! empty($narrative)) {
                fputcsv($handle, ['Narrative']);
                if (! empty($narrative['overview'])) {
                    fputcsv($handle, ['Overview', $narrative['overview']]);
                }
                foreach (['highlights' => 'Highlights', 'concerns' => 'Concerns', 'recommendations' => 'Recommendations'] as $key => $title) {
                    if (! empty($narrative[$key])) {
                        $text = is_array($narrative[$key]) ? implode('; ', $narrative[$key]) : $narrative[$key];
                        fputcsv($handle, [$title, $text]);
                    }
                }
                fputcsv($handle, []);
            }

            foreach ($tables as $tableName => $table) {
                fputcsv($handle, [\Illuminate\Support\Str::of($tableName)->replace('_', ' ')->title()]);
                if (! empty($table['headers'])) {
                    fputcsv($handle, $table['headers']);
                }
                if (! empty($table['rows'])) {
                    foreach ($table['rows'] as $row) {
                        $rowValues = [];
                        foreach ($row as $cell) {
                            $rowValues[] = is_numeric($cell) ? number_format($cell, 2) : (is_array($cell) ? json_encode($cell) : $cell);
                        }
                        fputcsv($handle, $rowValues);
                    }
                } else {
                    fputcsv($handle, ['No rows available.']);
                }
                fputcsv($handle, []);
            }

            fclose($handle);
        }, $csvFilename);
    }

    private function canExportReports($user): bool
    {
        $checks = [
            'export-reports',
            'export_reports',
            'export-inventory-reports',
            'export_inventory_reports',
        ];

        foreach ($checks as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}

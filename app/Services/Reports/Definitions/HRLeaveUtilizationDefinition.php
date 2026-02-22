<?php

namespace App\Services\Reports\Definitions;

use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveApplication;
use Carbon\Carbon;

class HRLeaveUtilizationDefinition implements ReportDefinition
{
    public function meta(): array
    {
        return [
            'name' => 'HR Leave Utilization',
            'type' => 'leave_utilization',
            'category' => 'hr',
            'requires_department' => false,
            'permissions' => [
                'manage-organization',
                'manage-leave',
                'view-hr-reports',
            ],
            'order' => 2,
        ];
    }

    public function query(array $context): array
    {
        $branchId = $context['branch_id'] ?? null;
        $from = $context['period_from'] ?? null;
        $to = $context['period_to'] ?? null;

        $leaveQuery = LeaveApplication::query()
            ->with(['employee', 'leaveType'])
            ->when($branchId, function ($q) use ($branchId) {
                $q->whereHas('employee', fn ($employee) => $employee->where('branch_id', $branchId));
            })
            ->when($from && $to, function ($q) use ($from, $to) {
                $q->whereDate('start_date', '<=', $to)
                    ->whereDate('end_date', '>=', $from);
            });

        $leaveRequests = $leaveQuery->get();

        $year = $from ? Carbon::parse($from)->year : now()->year;
        $balancesQuery = EmployeeLeaveBalance::query()
            ->with(['employee', 'leaveType'])
            ->where('year', $year)
            ->when($branchId, function ($q) use ($branchId) {
                $q->whereHas('employee', fn ($employee) => $employee->where('branch_id', $branchId));
            });

        $balances = $balancesQuery->get();

        $leaveTypeSummary = $leaveRequests
            ->groupBy('leave_type_id')
            ->map(function ($items) {
                $leaveType = $items->first()?->leaveType?->name ?? 'Unspecified';
                $approvedDays = $items->where('status', 'approved')->sum('total_days');
                $pendingDays = $items->where('status', 'pending')->sum('total_days');

                return [
                    'leave_type' => $leaveType,
                    'requests' => $items->count(),
                    'approved_days' => $approvedDays,
                    'pending_days' => $pendingDays,
                    'approved_requests' => $items->where('status', 'approved')->count(),
                    'pending_requests' => $items->where('status', 'pending')->count(),
                ];
            })
            ->sortByDesc('requests')
            ->values()
            ->all();

        $employeeUtilization = $leaveRequests
            ->where('status', 'approved')
            ->groupBy('employee_id')
            ->map(function ($items) {
                $employee = $items->first()?->employee;
                return [
                    'employee' => $employee?->name ?? 'Unknown',
                    'department' => $employee?->department?->name ?? 'Unassigned',
                    'approved_days' => $items->sum('total_days'),
                    'requests' => $items->count(),
                ];
            })
            ->sortByDesc('approved_days')
            ->values()
            ->take(15)
            ->all();

        $pendingApprovals = $leaveRequests
            ->where('status', 'pending')
            ->sortBy('start_date')
            ->take(15)
            ->map(function ($leave) {
                return [
                    'employee' => $leave->employee?->name ?? 'Unknown',
                    'leave_type' => $leave->leaveType?->name ?? 'Unspecified',
                    'start_date' => optional($leave->start_date)->format('Y-m-d'),
                    'end_date' => optional($leave->end_date)->format('Y-m-d'),
                    'days' => $leave->total_days ?? 0,
                ];
            })
            ->values()
            ->all();

        $balanceSummary = [
            'total_allocated' => (float) $balances->sum('total_days'),
            'total_used' => (float) $balances->sum('used_days'),
            'total_pending' => (float) $balances->sum('pending_days'),
            'total_remaining' => (float) $balances->sum('remaining_days'),
        ];

        $balancesByType = $balances
            ->groupBy('leave_type_id')
            ->map(function ($items) {
                $leaveType = $items->first()?->leaveType?->name ?? 'Unspecified';
                return [
                    'leave_type' => $leaveType,
                    'allocated' => (float) $items->sum('total_days'),
                    'used' => (float) $items->sum('used_days'),
                    'pending' => (float) $items->sum('pending_days'),
                    'remaining' => (float) $items->sum('remaining_days'),
                ];
            })
            ->sortByDesc('allocated')
            ->values()
            ->all();

        return [
            'leave_summary' => $balanceSummary,
            'leave_types' => $leaveTypeSummary,
            'employee_utilization' => $employeeUtilization,
            'pending_approvals' => $pendingApprovals,
            'balances_by_type' => $balancesByType,
            'period_info' => [
                'from' => $from,
                'to' => $to,
                'total_days' => $from && $to ? Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1 : 0,
                'year' => $year,
            ],
        ];
    }

    public function summary(array $data, array $context): array
    {
        $summary = $data['leave_summary'] ?? [];

        return [
            'total_allocated_days' => $summary['total_allocated'] ?? 0,
            'total_used_days' => $summary['total_used'] ?? 0,
            'total_pending_days' => $summary['total_pending'] ?? 0,
            'total_remaining_days' => $summary['total_remaining'] ?? 0,
            'leave_types_covered' => count($data['leave_types'] ?? []),
            'pending_requests' => count($data['pending_approvals'] ?? []),
        ];
    }

    public function charts(array $data, array $context): array
    {
        $types = $data['balances_by_type'] ?? [];

        return [
            'leave_allocation_chart' => [
                'type' => 'bar',
                'labels' => array_column($types, 'leave_type'),
                'datasets' => [
                    [
                        'label' => 'Allocated',
                        'data' => array_column($types, 'allocated'),
                        'color' => '#3b82f6',
                    ],
                    [
                        'label' => 'Used',
                        'data' => array_column($types, 'used'),
                        'color' => '#10b981',
                    ],
                    [
                        'label' => 'Remaining',
                        'data' => array_column($types, 'remaining'),
                        'color' => '#f59e0b',
                    ],
                ],
            ],
        ];
    }

    public function tables(array $data, array $summary, array $context): array
    {
        return [
            'leave_types' => [
                'headers' => ['Leave Type', 'Requests', 'Approved Days', 'Pending Days'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['leave_type'],
                        $row['requests'],
                        $row['approved_days'],
                        $row['pending_days'],
                    ];
                }, $data['leave_types'] ?? []),
            ],
            'balances_by_type' => [
                'headers' => ['Leave Type', 'Allocated', 'Used', 'Pending', 'Remaining'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['leave_type'],
                        $row['allocated'],
                        $row['used'],
                        $row['pending'],
                        $row['remaining'],
                    ];
                }, $data['balances_by_type'] ?? []),
            ],
            'employee_utilization' => [
                'headers' => ['Employee', 'Department', 'Approved Days', 'Requests'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['employee'],
                        $row['department'],
                        $row['approved_days'],
                        $row['requests'],
                    ];
                }, $data['employee_utilization'] ?? []),
            ],
            'pending_approvals' => [
                'headers' => ['Employee', 'Leave Type', 'Start', 'End', 'Days'],
                'rows' => array_map(function ($row) {
                    return [
                        $row['employee'],
                        $row['leave_type'],
                        $row['start_date'],
                        $row['end_date'],
                        $row['days'],
                    ];
                }, $data['pending_approvals'] ?? []),
            ],
        ];
    }

    public function narrative(array $data, array $summary, array $context): array
    {
        $highlights = [];
        $concerns = [];
        $recommendations = [];

        $used = $summary['total_used_days'] ?? 0;
        $allocated = $summary['total_allocated_days'] ?? 0;

        if ($allocated > 0) {
            $usageRate = round(($used / $allocated) * 100, 1);
            $highlights[] = "Leave utilization rate is {$usageRate}% for the selected period.";
        }

        if (($summary['pending_requests'] ?? 0) > 10) {
            $concerns[] = 'High volume of pending leave requests awaiting approval.';
            $recommendations[] = 'Review pending leave approvals to reduce backlog.';
        }

        return [
            'overview' => "Leave utilization summary: {$summary['total_used_days']} days used out of {$summary['total_allocated_days']} allocated.",
            'highlights' => $highlights,
            'concerns' => $concerns,
            'recommendations' => $recommendations,
        ];
    }
}

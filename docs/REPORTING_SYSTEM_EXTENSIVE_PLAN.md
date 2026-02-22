# Reporting System - Extensive Implementation Plan

**Date:** 2026-02-17  
**Status:** Implementation Ready  
**Owner:** Development Team

---

## Executive Summary

This document provides an extensive, detailed implementation plan for the SweetTooth Reporting System. The system is **module-specific** (Production, Sales, Inventory, Accounting, HR), features **table-centric reports** with concise text, includes **print/export functionality**, and follows a strict **lifecycle workflow** (Generate → Review → Push → Compile → Send to MD).

### Key Design Principles

1. **Tables First:** All reports prioritize tabular data presentation with short, actionable text
2. **Module Autonomy:** Each module owns its report generation with standardized interfaces
3. **Data Integrity:** System-generated data is immutable; annotations are separate
4. **Role-Based Access:** Strict permissions control report visibility and actions
5. **Audit Trail:** Every state change is logged with full traceability

---

## Current State Assessment

### ✅ Already Implemented

| Component | Status | Location |
|-----------|--------|----------|
| DepartmentReport Model | ✅ Complete | `app/Models/DepartmentReport.php` |
| CompiledReport Model | ✅ Complete | `app/Models/CompiledReport.php` |
| ReportAnnotation Model | ✅ Complete | `app/Models/ReportAnnotation.php` |
| ReportRegistry Service | ✅ Complete | `app/Services/Reports/ReportRegistry.php` |
| ReportService Base Class | ✅ Complete | `app/Services/Reports/ReportService.php` |
| ReportDefinition Interface | ✅ Complete | `app/Services/Reports/Definitions/ReportDefinition.php` |
| Central Reporting Dashboard | ✅ Partial | `app/Livewire/BranchDashboard/ReportingDepartment/Dashboard` |
| Review Reports UI | ✅ Partial | `app/Livewire/BranchDashboard/ReportingDepartment/ReviewReports` |
| Generate Report UI | ✅ Partial | `app/Livewire/BranchDashboard/Reporting/Generate` |
| Export Controller | ✅ Partial | `app/Http/Controllers/ExportController.php` |

### ⚠️ Gaps Identified

| Gap | Priority | Impact |
|-----|----------|--------|
| No ReportAuditLog model/migration | High | Cannot track state changes |
| Missing Print/PDF export with tables | High | Cannot produce physical reports |
| Incomplete module report engines | High | Not all modules have full reports |
| No Report Detail View with annotations | Medium | Cannot review full report context |
| Compile Reports workflow incomplete | Medium | Cannot consolidate reports |
| Send to MD workflow incomplete | Medium | MD cannot receive reports |
| No MD Dashboard view | Medium | MD has no dedicated view |
| Missing scheduled auto-generation | Low | Manual report generation only |

---

## Phase 1: Database & Foundation (Week 1)

### 1.1 Create ReportAuditLog Migration & Model

**File:** `database/migrations/2026_02_17_000001_create_report_audit_logs_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('reportable'); // department_report or compiled_report
            $table->uuid('actor_id')->nullable();
            $table->string('actor_type')->nullable();
            $table->string('action'); // generated, submitted, reviewed, approved, rejected, compiled, sent_to_md, viewed, exported, annotated
            $table->string('action_category')->default('state_change'); // state_change, data_access, export, annotation
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // {old_status, new_status, ip_address, user_agent}
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['reportable_type', 'reportable_id']);
            $table->index(['actor_type', 'actor_id']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_audit_logs');
    }
};
```

**File:** `app/Models/ReportAuditLog.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ReportAuditLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'reportable_id',
        'reportable_type',
        'actor_id',
        'actor_type',
        'action',
        'action_category',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): MorphTo
    {
        return $this->morphTo('actor', 'actor_type', 'actor_id');
    }

    public static function log(
        $reportable,
        string $action,
        string $description,
        array $metadata = []
    ): self {
        $user = auth()->user();

        return static::create([
            'reportable_id' => $reportable->getKey(),
            'reportable_type' => get_class($reportable),
            'actor_id' => $user?->getKey(),
            'actor_type' => $user ? get_class($user) : null,
            'action' => $action,
            'action_category' => self::categorizeAction($action),
            'description' => $description,
            'metadata' => array_merge([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ], $metadata),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    private static function categorizeAction(string $action): string
    {
        return match ($action) {
            'generated', 'submitted', 'reviewed', 'approved', 'rejected', 'compiled', 'sent_to_md' => 'state_change',
            'viewed', 'downloaded', 'printed' => 'data_access',
            'exported' => 'export',
            'annotated', 'comment_added' => 'annotation',
            default => 'other',
        };
    }
}
```

### 1.2 Update DepartmentReport Model

Add audit logging trait and methods:

```php
// Add to app/Models/DepartmentReport.php

public function auditLogs()
{
    return $this->morphMany(ReportAuditLog::class, 'reportable');
}

public function logAction(string $action, string $description, array $metadata = [])
{
    return ReportAuditLog::log($this, $action, $description, $metadata);
}

// Update status change methods to log
public function markAsReviewed($employeeId, $employeeType, $notes = null)
{
    $oldStatus = $this->status;
    $this->update([
        'status' => 'reviewed',
        'reviewed_by_id' => $employeeId,
        'reviewed_by_type' => $employeeType,
        'reviewed_at' => now(),
        'review_notes' => $notes,
    ]);
    $this->logAction('reviewed', 'Report reviewed and approved', [
        'old_status' => $oldStatus,
        'new_status' => 'reviewed',
        'reviewer_id' => $employeeId,
    ]);
}

public function submitForReview()
{
    $oldStatus = $this->status;
    $this->update(['status' => 'pending_review']);
    $this->logAction('submitted', 'Report submitted for review', [
        'old_status' => $oldStatus,
        'new_status' => 'pending_review',
    ]);
}
```

### 1.3 Update CompiledReport Model

```php
// Add to app/Models/CompiledReport.php

public function auditLogs()
{
    return $this->morphMany(ReportAuditLog::class, 'reportable');
}

public function logAction(string $action, string $description, array $metadata = [])
{
    return ReportAuditLog::log($this, $action, $description, $metadata);
}
```

---

## Phase 2: Module-Specific Report Engines (Week 2-3)

### 2.1 Production Module Reports

**Reports Required:**

| Report | Definition | Service | View | Status |
|--------|-----------|---------|------|--------|
| Daily Production Summary | ✅ Exists | ✅ Exists | ✅ Exists | Complete |
| Production Efficiency | ✅ Exists | ✅ Exists | ✅ Exists | Complete |
| Quality Metrics | ✅ Exists | ✅ Exists | ✅ Exists | Complete |
| Cost Analysis | ✅ Exists | ✅ Exists | ✅ Exists | Complete |
| Waste Analysis | ✅ Exists | ✅ Exists | ✅ Exists | Complete |
| **Production Output Table** | ❌ Missing | ❌ Missing | ❌ Missing | **NEW** |

**New Report: Production Output Table**

File: `app/Services/Reports/Definitions/ProductionOutputTableDefinition.php`

```php
<?php

namespace App\Services\Reports\Definitions;

use App\Models\DailyProduce;
use App\Models\ProductionRecord;
use App\Models\Recipe;
use Carbon\Carbon;

class ProductionOutputTableDefinition implements ReportDefinition
{
    public function meta(): array
    {
        return [
            'name' => 'Production Output Table',
            'type' => 'output_table',
            'category' => 'production',
            'description' => 'Detailed tabular view of all production output',
            'requires_department' => true,
            'permissions' => ['view-production-reports'],
            'order' => 1,
            'table_focused' => true,
        ];
    }

    public function query(array $context): array
    {
        $branchId = $context['branch_id'];
        $departmentId = $context['department_id'];
        $from = $context['period_from'];
        $to = $context['period_to'];

        $produces = DailyProduce::with(['recipe', 'shift', 'producedBy'])
            ->whereHas('shift', function ($q) use ($branchId, $departmentId) {
                $q->where('branch_id', $branchId);
                if ($departmentId) {
                    $q->where('department_id', $departmentId);
                }
            })
            ->whereBetween('produce_date', [$from, $to])
            ->orderBy('produce_date')
            ->orderBy('shift_id')
            ->get();

        return [
            'produces' => $produces,
            'total_batches' => $produces->count(),
            'unique_products' => $produces->unique('recipe_id')->count(),
            'date_range' => ['from' => $from, 'to' => $to],
        ];
    }

    public function summary(array $data, array $context): array
    {
        $produces = collect($data['produces']);

        return [
            'total_produced' => $produces->sum('produced_quantity'),
            'total_requested' => $produces->sum('requested_quantity'),
            'total_variance' => $produces->sum('variance'),
            'efficiency' => $produces->sum('requested_quantity') > 0
                ? round(($produces->sum('produced_quantity') / $produces->sum('requested_quantity')) * 100, 2)
                : 0,
            'total_batches' => $data['total_batches'],
            'unique_products' => $data['unique_products'],
        ];
    }

    public function charts(array $data, array $context): array
    {
        return []; // Table-focused report, minimal charts
    }

    public function tables(array $data, array $summary, array $context): array
    {
        $produces = collect($data['produces']);

        return [
            'production_output_detail' => [
                'headers' => ['Date', 'Shift', 'Product', 'Requested', 'Produced', 'Variance', 'Efficiency %', 'Status'],
                'rows' => $produces->map(function ($p) {
                    $efficiency = $p->requested_quantity > 0
                        ? round(($p->produced_quantity / $p->requested_quantity) * 100, 1)
                        : 0;
                    $status = $p->variance >= 0 ? 'On Target' : 'Below Target';

                    return [
                        $p->produce_date->format('Y-m-d'),
                        $p->shift?->name ?? 'N/A',
                        $p->recipe?->product_name ?? 'Unknown',
                        number_format($p->requested_quantity, 2),
                        number_format($p->produced_quantity, 2),
                        number_format($p->variance, 2),
                        $efficiency . '%',
                        $status,
                    ];
                })->toArray(),
            ],
            'production_summary_by_product' => [
                'headers' => ['Product', 'Batches', 'Total Requested', 'Total Produced', 'Total Variance', 'Avg Efficiency %'],
                'rows' => $produces->groupBy('recipe_id')->map(function ($group, $recipeId) {
                    $first = $group->first();
                    $totalRequested = $group->sum('requested_quantity');
                    $totalProduced = $group->sum('produced_quantity');
                    $avgEfficiency = $totalRequested > 0
                        ? round(($totalProduced / $totalRequested) * 100, 1)
                        : 0;

                    return [
                        $first->recipe?->product_name ?? 'Unknown',
                        $group->count(),
                        number_format($totalRequested, 2),
                        number_format($totalProduced, 2),
                        number_format($totalProduced - $totalRequested, 2),
                        $avgEfficiency . '%',
                    ];
                })->values()->toArray(),
            ],
        ];
    }

    public function narrative(array $data, array $summary, array $context): array
    {
        return [
            'overview' => "Produced {$summary['total_produced']} units across {$summary['total_batches']} batches. Efficiency: {$summary['efficiency']}%.",
            'highlights' => [],
            'concerns' => [],
            'recommendations' => [],
        ];
    }
}
```

Register in ReportRegistry:

```php
// Add to app/Services/Reports/ReportRegistry.php::all()
[
    'definition' => ProductionOutputTableDefinition::class,
    'service' => ProductionOutputTableService::class, // Can extend ReportService
],
```

### 2.2 Sales Module Reports

**Reports Required:**

| Report | Definition | Service | View | Status |
|--------|-----------|---------|------|--------|
| Sales Performance | ✅ Exists | ✅ Exists | ✅ Exists | Complete |
| **Daily Sales Table** | ❌ Missing | ❌ Missing | ❌ Missing | **NEW** |
| **Customer Orders Summary** | ❌ Missing | ❌ Missing | ❌ Missing | **NEW** |
| **POS Transactions Table** | ❌ Missing | ❌ Missing | ❌ Missing | **NEW** |

**New Report: Daily Sales Table**

File: `app/Services/Reports/Definitions/SalesDailyTableDefinition.php`

```php
<?php

namespace App\Services\Reports\Definitions;

use App\Models\Payment; // Or your sales/transaction model
use Carbon\Carbon;

class SalesDailyTableDefinition implements ReportDefinition
{
    public function meta(): array
    {
        return [
            'name' => 'Daily Sales Table',
            'type' => 'daily_sales_table',
            'category' => 'sales',
            'description' => 'Detailed daily sales transactions',
            'requires_department' => true,
            'permissions' => ['view-sales-reports'],
            'order' => 1,
            'table_focused' => true,
        ];
    }

    public function query(array $context): array
    {
        // Adapt to your actual sales data model
        $branchId = $context['branch_id'];
        $from = $context['period_from'];
        $to = $context['period_to'];

        $sales = Payment::with(['user', 'branch'])
            ->where('branch_id', $branchId)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'sales' => $sales,
            'total_transactions' => $sales->count(),
        ];
    }

    public function summary(array $data, array $context): array
    {
        $sales = collect($data['sales']);

        return [
            'total_revenue' => $sales->sum('amount'),
            'total_transactions' => $data['total_transactions'],
            'average_transaction' => $sales->count() > 0 ? $sales->sum('amount') / $sales->count() : 0,
        ];
    }

    public function charts(array $data, array $context): array
    {
        return [];
    }

    public function tables(array $data, array $summary, array $context): array
    {
        $sales = collect($data['sales']);

        return [
            'daily_sales_detail' => [
                'headers' => ['Date', 'Time', 'Transaction ID', 'Amount', 'Payment Method', 'User', 'Status'],
                'rows' => $sales->map(function ($s) {
                    return [
                        $s->created_at->format('Y-m-d'),
                        $s->created_at->format('H:i'),
                        $s->id,
                        number_format($s->amount, 2),
                        $s->payment_method ?? 'Cash',
                        $s->user?->name ?? 'N/A',
                        ucfirst($s->status ?? 'completed'),
                    ];
                })->toArray(),
            ],
        ];
    }

    public function narrative(array $data, array $summary, array $context): array
    {
        return [
            'overview' => "Total revenue: " . number_format($summary['total_revenue'], 2) . " from {$summary['total_transactions']} transactions.",
            'highlights' => [],
            'concerns' => [],
            'recommendations' => [],
        ];
    }
}
```

### 2.3 Inventory Module Reports

**Reports Required:**

| Report | Definition | Service | View | Status |
|--------|-----------|---------|------|--------|
| Stock Levels | ✅ Exists (Simple) | ⚠️ Partial | ❌ Missing | Needs View |
| Stock Movement | ⚠️ Partial | ⚠️ Partial | ❌ Missing | **NEW** |
| **Stock Ledger Table** | ❌ Missing | ❌ Missing | ❌ Missing | **NEW** |
| **Inventory Valuation Table** | ❌ Missing | ❌ Missing | ❌ Missing | **NEW** |
| **Expiry Report Table** | ❌ Missing | ❌ Missing | ❌ Missing | **NEW** |

### 2.4 Accounting Module Reports

**Reports Required:**

| Report | Definition | Service | View | Status |
|--------|-----------|---------|------|--------|
| Income Statement | ✅ Exists | ✅ Exists | ✅ Exists | Complete |
| Balance Sheet | ✅ Exists | ✅ Exists | ✅ Exists | Complete |
| Trial Balance | ✅ Exists | ✅ Exists | ✅ Exists | Complete |
| General Ledger | ✅ Exists | ✅ Exists | ✅ Exists | Complete |
| **Journal Entry Table** | ❌ Missing | ❌ Missing | ❌ Missing | **NEW** |
| **Cash Flow Table** | ❌ Missing | ❌ Missing | ❌ Missing | **NEW** |

### 2.5 HR Module Reports

**Reports Required:**

| Report | Definition | Service | View | Status |
|--------|-----------|---------|------|--------|
| Workforce Overview | ✅ Exists | ✅ Exists | ✅ Exists | Complete |
| Leave Utilization | ✅ Exists | ✅ Exists | ❌ Missing | Needs View |
| **Employee Attendance Table** | ❌ Missing | ❌ Missing | ❌ Missing | **NEW** |
| **Payroll Summary Table** | ❌ Missing | ❌ Missing | ❌ Missing | **NEW** |
| **Appraisal Status Table** | ❌ Missing | ❌ Missing | ❌ Missing | **NEW** |

---

## Phase 3: Central Reporting Dashboard & Review System (Week 4)

### 3.1 Reporting Department Dashboard Enhancement

**File:** `resources/views/livewire/branch-dashboard/reporting-department/dashboard/index.blade.php`

Complete redesign with table-centric layout:

```blade
<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Reporting Dashboard</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Monitor and manage all department reports</p>
        </div>
        <a href="{{ branch_route('branch-dashboard.reporting.generate') }}"
           class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
            <x-icon name="plus" class="w-5 h-5 mr-2" />
            Generate Report
        </a>
    </div>

    {{-- Status Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-stat-card title="Pending Review" :value="$stats['pending_review']" icon="clock" color="orange" />
        <x-stat-card title="Reviewed" :value="$stats['reviewed']" icon="check" color="green" />
        <x-stat-card title="Compiled Today" :value="$stats['compiled_today']" icon="document" color="blue" />
        <x-stat-card title="Sent to MD" :value="$stats['sent_to_md']" icon="paper-airplane" color="purple" />
    </div>

    {{-- Reports by Module Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Reports by Module (This Month)</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Module</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Draft</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Pending</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reviewed</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Compiled</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sent</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach(['production', 'sales', 'inventory', 'accounting', 'hr'] as $module)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                            {{ ucfirst($module) }}
                        </td>
                        @foreach(['draft', 'pending_review', 'reviewed', 'compiled', 'sent_to_md'] as $status)
                            <td class="px-6 py-4 text-sm text-center text-gray-700 dark:text-gray-300">
                                {{ $stats["{$module}_{$status}"] ?? 0 }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Recent Activity Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Report Activity</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Report</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Module</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Last Action</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentReports as $report)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $report->report_name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ ucfirst($report->report_category) }}</td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$report->status] }}">
                                {{ ucfirst(str_replace('_', ' ', $report->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $report->last_action }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $report->updated_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">No recent activity</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
```

### 3.2 Review Reports Enhancement

Add full report view with annotations inline:

**File:** `app/Livewire/BranchDashboard/ReportingDepartment/ReportDetail/View.php`

```php
<?php

namespace App\Livewire\BranchDashboard\ReportingDepartment\ReportDetail;

use App\Models\DepartmentReport;
use App\Models\ReportAnnotation;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
#[Title('Report Detail')]
class View extends Component
{
    public $reportId;
    public $report;
    public $annotations = [];
    public $newAnnotation = '';

    public function mount($id)
    {
        $this->reportId = $id;
        $this->report = DepartmentReport::with(['department', 'generatedBy', 'reviewedBy', 'auditLogs'])
            ->findOrFail($id);
        $this->annotations = $this->report->annotations()
            ->with('author')
            ->orderBy('created_at')
            ->get();
    }

    public function addAnnotation()
    {
        $this->validate(['newAnnotation' => 'required|string|max:5000']);

        $actor = current_actor();
        $this->report->annotations()->create([
            'author_id' => $actor?->getKey() ?? auth()->id(),
            'author_type' => $actor ? get_class($actor) : get_class(auth()->user()),
            'body' => $this->newAnnotation,
        ]);

        $this->report->logAction('annotated', 'Annotation added');
        $this->newAnnotation = '';
        $this->annotations = $this->report->annotations()
            ->with('author')
            ->orderBy('created_at')
            ->get();

        $this->dispatch('notification', ['type' => 'success', 'message' => 'Annotation added']);
    }

    public function approveReport()
    {
        $actor = current_actor();
        $this->report->markAsReviewed($actor?->getKey(), get_class($actor));
        $this->dispatch('notification', ['type' => 'success', 'message' => 'Report approved']);
        $this->dispatch('$refresh');
    }

    public function rejectReport($reason)
    {
        $this->report->update(['status' => 'rejected', 'review_notes' => $reason]);
        $this->report->logAction('rejected', 'Report rejected', ['reason' => $reason]);
        $this->dispatch('notification', ['type' => 'success', 'message' => 'Report rejected']);
        $this->dispatch('$refresh');
    }

    public function render()
    {
        return view('livewire.branch-dashboard.reporting-department.report-detail.view');
    }
}
```

---

## Phase 4: Print/Export Functionality (Week 5)

### 4.1 PDF Export Service

**File:** `app/Services/Reports/ReportPdfExporter.php`

```php
<?php

namespace App\Services\Reports;

use App\Models\DepartmentReport;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportPdfExporter
{
    public static function export(DepartmentReport $report, string $orientation = 'landscape'): \Illuminate\Http\Response
    {
        $payload = $report->report_data ?? [];
        $summary = $report->summary_metrics ?? ($payload['summary_metrics'] ?? []);
        $tables = $payload['tables'] ?? [];
        $narrative = $payload['narrative'] ?? [];

        $pdf = Pdf::loadView('exports.reports.department-report-pdf', [
            'report' => $report,
            'summary' => $summary,
            'tables' => $tables,
            'narrative' => $narrative,
            'generatedAt' => now()->format('Y-m-d H:i:s'),
        ])
        ->setPaper('a4', $orientation)
        ->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
        ]);

        $filename = sprintf(
            'report-%s-%s-%s.pdf',
            $report->report_category,
            $report->report_type,
            $report->report_date
        );

        return $pdf->download($filename);
    }

    public static function exportCompiled($compiledReport): \Illuminate\Http\Response
    {
        // Similar logic for compiled reports
    }
}
```

### 4.2 Print View (Table-Centric)

**File:** `resources/views/exports/reports/department-report-pdf.blade.php`

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $report->report_name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h1 { font-size: 18px; margin-bottom: 5px; }
        h2 { font-size: 14px; margin-top: 15px; border-bottom: 2px solid #333; padding-bottom: 3px; }
        .header-info { margin-bottom: 15px; }
        .header-info table { width: 100%; border-collapse: collapse; }
        .header-info td { padding: 3px; font-size: 10px; }
        .label { font-weight: bold; color: #555; }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9px;
        }
        table.data-table th {
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 6px 4px;
            text-align: left;
            font-weight: 600;
            font-size: 9px;
        }
        table.data-table td {
            border: 1px solid #d1d5db;
            padding: 5px 4px;
        }
        table.data-table tr:nth-child(even) { background-color: #f9fafb; }
        table.data-table tr:hover { background-color: #f3f4f6; }

        .summary-grid { display: table; width: 100%; margin: 10px 0; }
        .summary-row { display: table-row; }
        .summary-cell {
            display: table-cell;
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: center;
            width: 20%;
        }
        .summary-label { font-size: 9px; color: #666; }
        .summary-value { font-size: 16px; font-weight: bold; color: #111; margin-top: 3px; }

        .narrative { margin: 10px 0; padding: 8px; background: #f9fafb; border-left: 3px solid #3b82f6; }
        .narrative h3 { margin: 0 0 5px 0; font-size: 11px; }
        .narrative p { margin: 0; font-size: 10px; line-height: 1.4; }

        .footer {
            position: fixed; bottom: -10px; left: 0; right: 0;
            font-size: 8px; color: #888; text-align: center;
            border-top: 1px solid #e5e7eb; padding-top: 5px;
        }
        .page-number::after { content: " Page " counter(page) " of " counter(pages); }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header-info">
        <h1>{{ $report->report_name }}</h1>
        <table>
            <tr>
                <td><span class="label">Module:</span> {{ ucfirst($report->report_category) }}</td>
                <td><span class="label">Type:</span> {{ $report->report_type }}</td>
                <td><span class="label">Department:</span> {{ $report->department?->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><span class="label">Period:</span> {{ $report->period_from }} to {{ $report->period_to }}</td>
                <td><span class="label">Generated:</span> {{ $report->report_date }}</td>
                <td><span class="label">Status:</span> {{ ucfirst(str_replace('_', ' ', $report->status)) }}</td>
            </tr>
        </table>
    </div>

    {{-- Summary Metrics --}}
    @if(!empty($summary))
        <h2>Summary</h2>
        <div class="summary-grid">
            <div class="summary-row">
                @foreach(array_slice($summary, 0, 5) as $label => $value)
                    <div class="summary-cell">
                        <div class="summary-label">{{ str_replace('_', ' ', ucfirst($label)) }}</div>
                        <div class="summary-value">{{ is_numeric($value) ? number_format($value, 2) : $value }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Narrative --}}
    @if(!empty($narrative['overview']))
        <div class="narrative">
            <h3>Overview</h3>
            <p>{{ $narrative['overview'] }}</p>
        </div>
    @endif

    {{-- Tables --}}
    @foreach($tables as $tableName => $table)
        <h2>{{ str_replace('_', ' ', ucfirst($tableName)) }}</h2>
        <table class="data-table">
            @if(!empty($table['headers']))
                <thead>
                    <tr>
                        @foreach($table['headers'] as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody>
                @forelse($table['rows'] ?? [] as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($table['headers'] ?? []) }}">No data</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    {{-- Footer --}}
    <div class="footer">
        Generated: {{ $generatedAt }} | Report ID: {{ $report->id }}
        <span class="page-number"></span>
    </div>
</body>
</html>
```

### 4.3 Print Button Component

**File:** `resources/views/components/print-button.blade.php`

```blade
@props(['reportId', 'reportType' => 'department'])

<button
    onclick="printReport('{{ $reportId }}', '{{ $reportType }}')"
    {{ $attributes->merge(['class' => 'inline-flex items-center px-3 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-900']) }}
>
    <x-icon name="printer" class="w-4 h-4 mr-2" />
    {{ $slot ?? 'Print' }}
</button>

<script>
function printReport(reportId, reportType) {
    const url = reportType === 'department'
        ? `/branch-dashboard/exports/department-report-pdf/${reportId}`
        : `/branch-dashboard/exports/compiled-report-pdf/${reportId}`;

    const printWindow = window.open(url, '_blank');
    printWindow.onload = function() {
        printWindow.print();
    };
}
</script>
```

### 4.4 Add PDF Export Route

**File:** `routes/branch-route.php`

```php
// Add to existing routes
Route::prefix('exports')->name('exports.')->group(function () {
    Route::get('department-report/{reportId}', [ExportController::class, 'departmentReport'])
        ->name('department-report');
    Route::get('department-report-pdf/{reportId}', [ExportController::class, 'departmentReportPdf'])
        ->name('department-report-pdf');
    Route::get('compiled-report-pdf/{compiledReportId}', [ExportController::class, 'compiledReportPdf'])
        ->name('compiled-report-pdf');
});
```

**File:** `app/Http/Controllers/ExportController.php`

```php
// Add to ExportController
use App\Services\Reports\ReportPdfExporter;

public function departmentReportPdf(string $reportId)
{
    $user = Auth::guard('web')->user();
    $branchId = $user->branch_id ?? current_branch_id();

    $report = DepartmentReport::query()
        ->where('branch_id', $branchId)
        ->findOrFail($reportId);

    $report->logAction('printed', 'Report printed to PDF');

    return ReportPdfExporter::export($report);
}
```

---

## Phase 5: Compile & Send to MD Workflow (Week 6)

### 5.1 Compile Reports Livewire Component

**File:** `app/Livewire/BranchDashboard/ReportingDepartment/CompileReports/Index.php`

```php
<?php

namespace App\Livewire\BranchDashboard\ReportingDepartment\CompileReports;

use App\Models\CompiledReport;
use App\Models\DepartmentReport;
use App\Services\Reports\ReportCompilationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
#[Title('Compile Reports')]
class Index extends Component
{
    public $branchId;
    public $selectedReports = [];
    public $compilationTitle = '';
    public $compilationDescription = '';
    public $periodFrom;
    public $periodTo;

    public $availableReports = [];

    public function mount()
    {
        $this->branchId = current_branch_id();
        $this->periodFrom = now()->startOfMonth()->toDateString();
        $this->periodTo = now()->endOfMonth()->toDateString();
        $this->loadAvailableReports();
    }

    public function loadAvailableReports()
    {
        $this->availableReports = DepartmentReport::forBranch($this->branchId)
            ->whereIn('status', ['reviewed', 'compiled'])
            ->whereBetween('report_date', [$this->periodFrom, $this->periodTo])
            ->with(['department'])
            ->orderBy('report_date', 'desc')
            ->get()
            ->groupBy('report_category');
    }

    public function toggleReport($reportId)
    {
        if (in_array($reportId, $this->selectedReports)) {
            $this->selectedReports = array_diff($this->selectedReports, [$reportId]);
        } else {
            $this->selectedReports[] = $reportId;
        }
    }

    public function compile()
    {
        $this->validate([
            'compilationTitle' => 'required|string|max:255',
            'selectedReports' => 'required|array|min:1',
            'periodFrom' => 'required|date',
            'periodTo' => 'required|date|after_or_equal:periodFrom',
        ]);

        $service = app(ReportCompilationService::class);
        $compiledReport = $service->compile([
            'branch_id' => $this->branchId,
            'title' => $this->compilationTitle,
            'description' => $this->compilationDescription,
            'period_from' => $this->periodFrom,
            'period_to' => $this->periodTo,
            'report_ids' => $this->selectedReports,
            'compiled_by' => auth()->id(),
        ]);

        $this->dispatch('notification', ['type' => 'success', 'message' => 'Reports compiled successfully']);
        $this->redirect(branch_route('branch-dashboard.reporting-department.view-compiled.show', $compiledReport->id));
    }

    public function render()
    {
        return view('livewire.branch-dashboard.reporting-department.compile-reports.index');
    }
}
```

### 5.2 Send to MD Livewire Component

**File:** `app/Livewire/BranchDashboard/ReportingDepartment/SendToMD/Index.php`

```php
<?php

namespace App\Livewire\BranchDashboard\ReportingDepartment\SendToMD;

use App\Models\CompiledReport;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
#[Title('Send to Managing Director')]
class Index extends Component
{
    public $branchId;
    public $compiledReports = [];
    public $mdUsers = [];

    public function mount()
    {
        $this->branchId = current_branch_id();
        $this->loadCompiledReports();
        $this->loadMdUsers();
    }

    public function loadCompiledReports()
    {
        $this->compiledReports = CompiledReport::forBranch($this->branchId)
            ->where('status', 'approved')
            ->with(['compiledBy', 'departmentReports'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function loadMdUsers()
    {
        $this->mdUsers = User::role('Managing Director')->get();
    }

    public function sendToMD($compiledReportId, $mdUserId)
    {
        $compiledReport = CompiledReport::findOrFail($compiledReportId);

        // Only Super Admin can send to MD
        if (!auth()->user()->hasRole('Super Admin')) {
            $this->dispatch('notification', ['type' => 'error', 'message' => 'Only Super Admins can send to MD']);
            return;
        }

        $compiledReport->sendToMD($mdUserId);
        $compiledReport->logAction('sent_to_md', 'Report sent to Managing Director', [
            'md_user_id' => $mdUserId,
        ]);

        $this->dispatch('notification', ['type' => 'success', 'message' => 'Report sent to MD successfully']);
        $this->loadCompiledReports();
    }

    public function render()
    {
        return view('livewire.branch-dashboard.reporting-department.send-to-md.index');
    }
}
```

---

## Phase 6: MD Dashboard & View (Week 6)

### 6.1 MD Dashboard

**File:** `app/Livewire/BranchDashboard/MDReports/Dashboard/Index.php`

```php
<?php

namespace App\Livewire\BranchDashboard\MDReports\Dashboard;

use App\Models\CompiledReport;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
#[Title('MD Reports Dashboard')]
class Index extends Component
{
    public $branchId;
    public $stats = [];
    public $recentReports = [];

    public function mount()
    {
        $this->branchId = current_branch_id();
        $this->loadStats();
        $this->loadRecentReports();
    }

    public function loadStats()
    {
        $this->stats = [
            'total_received' => CompiledReport::where('md_user_id', auth()->id())->count(),
            'this_month' => CompiledReport::where('md_user_id', auth()->id())
                ->whereMonth('sent_to_md_at', now()->month)
                ->count(),
            'pending_review' => CompiledReport::where('md_user_id', auth()->id())
                ->whereNull('md_reviewed_at')
                ->count(),
        ];
    }

    public function loadRecentReports()
    {
        $this->recentReports = CompiledReport::where('md_user_id', auth()->id())
            ->with(['branch', 'compiledBy', 'departmentReports'])
            ->orderBy('sent_to_md_at', 'desc')
            ->limit(10)
            ->get();
    }

    public function markAsReviewed($reportId, $feedback = null)
    {
        $report = CompiledReport::findOrFail($reportId);
        $report->markAsReviewedByMD($feedback);
        $report->logAction('reviewed_by_md', 'MD reviewed report', ['feedback' => $feedback]);

        $this->dispatch('notification', ['type' => 'success', 'message' => 'Report marked as reviewed']);
        $this->loadRecentReports();
        $this->loadStats();
    }

    public function render()
    {
        return view('livewire.branch-dashboard.md-reports.dashboard.index');
    }
}
```

### 6.2 MD Report View (Read-Only)

**File:** `resources/views/livewire/branch-dashboard/md-reports/view-report/show.blade.php`

```blade
<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $report->compilation_title }}</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Compiled by {{ $report->compiledBy?->name }} | Sent: {{ $report->sent_to_md_at->format('Y-m-d H:i') }}
            </p>
        </div>
        <div class="flex gap-2">
            <x-print-button :report-id="$report->id" report-type="compiled" />
            <a href="{{ branch_route('branch-dashboard.exports.compiled-report-pdf', $report->id) }}"
               class="inline-flex items-center px-3 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">
                <x-icon name="arrow-down-tray" class="w-4 h-4 mr-2" />
                Download PDF
            </a>
        </div>
    </div>

    {{-- Executive Summary --}}
    @if(!empty($report->executive_summary))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Executive Summary</h2>
            <p class="text-gray-700 dark:text-gray-300">{{ $report->executive_summary['overview'] ?? '' }}</p>
        </div>
    @endif

    {{-- Key Metrics Table --}}
    @if(!empty($report->key_metrics))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Key Metrics</h2>
            </div>
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Metric</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Value</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Module</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($report->key_metrics as $metric)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $metric['name'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $metric['value'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ ucfirst($metric['module']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Included Reports Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Included Department Reports</h2>
        </div>
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Report Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Module</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Period</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($report->departmentReports as $deptReport)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $deptReport->report_name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ ucfirst($deptReport->report_category) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $deptReport->department?->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                            {{ $deptReport->period_from }} to {{ $deptReport->period_to }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <button wire:click="viewReport('{{ $deptReport->id }}')"
                                    class="text-indigo-600 hover:text-indigo-900">
                                View Details
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- MD Feedback --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Your Feedback</h2>
        <textarea wire:model="feedback" rows="4"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                  placeholder="Add your feedback or approval notes..."></textarea>
        <div class="mt-4 flex gap-2">
            <button wire:click="markAsReviewed"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                <x-icon name="check" class="w-5 h-5 mr-2" />
                Mark as Reviewed
            </button>
            <button wire:click="markAsReviewedWithFeedback"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <x-icon name="chat-bubble" class="w-5 h-5 mr-2" />
                Save Feedback & Review
            </button>
        </div>
    </div>
</div>
```

---

## Appendix A: Complete Module Report Matrix

| Module | Report Type | Definition | Service | View | Export | Print |
|--------|-------------|-----------|---------|------|--------|-------|
| **Production** | Daily Summary | ✅ | ✅ | ✅ | ✅ | ✅ |
| | Efficiency | ✅ | ✅ | ✅ | ✅ | ✅ |
| | Quality | ✅ | ✅ | ✅ | ✅ | ✅ |
| | Cost Analysis | ✅ | ✅ | ✅ | ✅ | ✅ |
| | Waste Analysis | ✅ | ✅ | ✅ | ✅ | ✅ |
| | Output Table | 🔧 NEW | 🔧 NEW | 🔧 NEW | ✅ | ✅ |
| **Sales** | Performance | ✅ | ✅ | ✅ | ✅ | ✅ |
| | Daily Sales Table | 🔧 NEW | 🔧 NEW | 🔧 NEW | ✅ | ✅ |
| | Customer Orders | 🔧 NEW | 🔧 NEW | 🔧 NEW | ✅ | ✅ |
| | POS Transactions | 🔧 NEW | 🔧 NEW | 🔧 NEW | ✅ | ✅ |
| **Inventory** | Stock Levels | ⚠️ | ⚠️ | 🔧 NEW | ✅ | ✅ |
| | Stock Movement | ⚠️ | ⚠️ | 🔧 NEW | ✅ | ✅ |
| | Stock Ledger | 🔧 NEW | 🔧 NEW | 🔧 NEW | ✅ | ✅ |
| | Valuation Table | 🔧 NEW | 🔧 NEW | 🔧 NEW | ✅ | ✅ |
| | Expiry Report | 🔧 NEW | 🔧 NEW | 🔧 NEW | ✅ | ✅ |
| **Accounting** | Income Statement | ✅ | ✅ | ✅ | ✅ | ✅ |
| | Balance Sheet | ✅ | ✅ | ✅ | ✅ | ✅ |
| | Trial Balance | ✅ | ✅ | ✅ | ✅ | ✅ |
| | General Ledger | ✅ | ✅ | ✅ | ✅ | ✅ |
| | Journal Table | 🔧 NEW | 🔧 NEW | 🔧 NEW | ✅ | ✅ |
| | Cash Flow Table | 🔧 NEW | 🔧 NEW | 🔧 NEW | ✅ | ✅ |
| **HR** | Workforce Overview | ✅ | ✅ | ✅ | ✅ | ✅ |
| | Leave Utilization | ✅ | ✅ | 🔧 NEW | ✅ | ✅ |
| | Attendance Table | 🔧 NEW | 🔧 NEW | 🔧 NEW | ✅ | ✅ |
| | Payroll Summary | 🔧 NEW | 🔧 NEW | 🔧 NEW | ✅ | ✅ |
| | Appraisal Status | 🔧 NEW | 🔧 NEW | 🔧 NEW | ✅ | ✅ |

**Legend:** ✅ Complete | ⚠️ Partial | 🔧 NEW (To be built)

---

## Appendix B: Report Lifecycle State Machine

```
┌─────────────┐
│   DRAFT     │ ◄── Report generated, editable
└──────┬──────┘
       │ Submit
       ▼
┌─────────────┐
│PENDING_REVIEW│ ◄── Awaiting approval
└──────┬──────┘
       ├──────────────┬────────────────┐
       │ Approve      │ Reject         │
       ▼              ▼                │
┌─────────────┐ ┌─────────────┐        │
│  REVIEWED   │ │  REJECTED   │────────┘
└──────┬──────┘ └─────────────┘
       │ Push
       ▼
┌─────────────┐
│   COMPILED  │ ◄── Combined with other reports
└──────┬──────┘
       │ Send (Super Admin only)
       ▼
┌─────────────┐
│  SENT_TO_MD │ ◄── MD can view & review
└─────────────┘
```

---

## Appendix C: Permission Matrix

| Action | Super Admin | Dept Admin | HR Admin | Employee | MD |
|--------|-------------|------------|----------|----------|-----|
| Generate Report | ✅ | ✅ | ✅ (HR) | ✅ | ❌ |
| Review Report | ✅ | ✅ (Dept) | ✅ (HR) | ❌ | ❌ |
| Push to Central | ✅ | ✅ (Dept) | ✅ (HR) | ❌ | ❌ |
| Compile Reports | ✅ | ❌ | ❌ | ❌ | ❌ |
| Send to MD | ✅ | ❌ | ❌ | ❌ | ❌ |
| View All Reports | ✅ | ⚠️ (Dept) | ⚠️ (HR) | ❌ | ✅ |
| View MD Reports | ❌ | ❌ | ❌ | ❌ | ✅ |
| Export PDF | ✅ | ✅ | ✅ | ❌ | ✅ |
| Add Annotation | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## Appendix D: File Structure

```
app/
├── Models/
│   ├── DepartmentReport.php
│   ├── CompiledReport.php
│   ├── ReportAnnotation.php
│   ├── ReportAuditLog.php (NEW)
│   ├── ReportDistribution.php
│   ├── ReportTemplate.php
│   └── ReportSchedule.php
├── Services/Reports/
│   ├── ReportRegistry.php
│   ├── ReportService.php
│   ├── ReportCompilationService.php
│   ├── ReportPdfExporter.php (NEW)
│   ├── Definitions/
│   │   ├── ReportDefinition.php (interface)
│   │   ├── ProductionOutputTableDefinition.php (NEW)
│   │   ├── SalesDailyTableDefinition.php (NEW)
│   │   ├── InventoryStockLedgerDefinition.php (NEW)
│   │   ├── AccountingJournalTableDefinition.php (NEW)
│   │   └── HRAttendanceTableDefinition.php (NEW)
│   └── [ReportName]Service.php
├── Livewire/BranchDashboard/
│   ├── Reporting/
│   │   └── Generate.php
│   ├── ReportingDepartment/
│   │   ├── Dashboard/Index.php
│   │   ├── ReviewReports/Index.php
│   │   ├── ReportDetail/View.php (NEW)
│   │   ├── CompileReports/Index.php (ENHANCED)
│   │   ├── SendToMD/Index.php (NEW)
│   │   └── ViewCompiled/Show.php
│   └── MDReports/
│       ├── Dashboard/Index.php
│       └── ViewReport/Show.php
├── Http/Controllers/
│   └── ExportController.php (ENHANCED)
└── Exports/
    └── [Module]ReportExport.php (for Excel exports)

resources/views/
├── livewire/branch-dashboard/
│   ├── reporting/
│   │   └── generate.blade.php
│   ├── reporting-department/
│   │   ├── dashboard/index.blade.php (ENHANCED)
│   │   ├── review-reports/index.blade.php (ENHANCED)
│   │   ├── report-detail/view.blade.php (NEW)
│   │   ├── compile-reports/index.blade.php (NEW)
│   │   ├── send-to-md/index.blade.php (NEW)
│   │   └── view-compiled/show.blade.php
│   └── md-reports/
│       ├── dashboard/index.blade.php (NEW)
│       └── view-report/show.blade.php (NEW)
└── exports/reports/
    ├── department-report-pdf.blade.php (NEW)
    └── compiled-report-pdf.blade.php (NEW)

routes/
├── branch-route.php (ENHANCED)
└── super-admin.php
```

---

## Appendix E: Testing Checklist

### Unit Tests
- [ ] ReportRegistry resolves reports correctly
- [ ] ReportService generates valid payloads
- [ ] DepartmentReport hash validation works
- [ ] ReportAuditLog captures all state changes
- [ ] Annotations are stored separately from system data

### Integration Tests
- [ ] Generate → Review → Push → Compile → Send workflow
- [ ] PDF export produces valid tables
- [ ] Excel export contains all data
- [ ] MD can view but not modify reports
- [ ] Permissions restrict access correctly

### UI Tests
- [ ] All tables render correctly
- [ ] Print button opens PDF in new window
- [ ] Review modal shows full report data
- [ ] Compile form validates report selection
- [ ] MD dashboard shows received reports

---

## Timeline Summary

| Phase | Duration | Deliverables |
|-------|----------|--------------|
| 1: Database & Foundation | Week 1 | AuditLog model, migrations, model updates |
| 2: Module Report Engines | Week 2-3 | 15+ new report definitions & services |
| 3: Central Dashboard | Week 4 | Enhanced dashboard, review system |
| 4: Print/Export | Week 5 | PDF exporter, print views, buttons |
| 5: Compile & Send | Week 6 | Compile workflow, Send to MD |
| 6: MD Dashboard | Week 6 | MD view, feedback system |

**Total Estimated Duration:** 6 weeks

---

## Next Steps

1. **Immediate:** Create ReportAuditLog migration and model
2. **Week 1:** Complete all database changes
3. **Week 2:** Start with Production module new reports
4. **Week 3:** Complete remaining module reports
5. **Week 4:** Enhance dashboard and review UI
6. **Week 5:** Implement PDF export and print functionality
7. **Week 6:** Complete compile/send workflow and MD dashboard

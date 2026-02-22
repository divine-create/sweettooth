<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompiledReport extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'compiled_by_id',
        'compiled_by_type',
        'compilation_title',
        'compilation_description',
        'compilation_date',
        'period_from',
        'period_to',
        'included_reports',
        'executive_summary',
        'key_metrics',
        'recommendations',
        'status',
        'approved_by_id',
        'approved_by_type',
        'approved_at',
        'sent_to_md_at',
        'md_user_id',
        'md_reviewed_at',
        'md_feedback',
        'file_path',
    ];

    protected $casts = [
        'compilation_date' => 'date',
        'period_from' => 'date',
        'period_to' => 'date',
        'included_reports' => 'array',
        'executive_summary' => 'array',
        'key_metrics' => 'array',
        'recommendations' => 'array',
        'approved_at' => 'datetime',
        'sent_to_md_at' => 'datetime',
        'md_reviewed_at' => 'datetime',
    ];

    /**
     * Get the branch that owns the compiled report.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the employee who compiled the report.
     */
    public function compiledBy(): MorphTo
    {
        return $this->morphTo('compiled_by', 'compiled_by_type', 'compiled_by_id');
    }

    /**
     * Get the employee who approved the report.
     */
    public function approvedBy(): MorphTo
    {
        return $this->morphTo('approved_by', 'approved_by_type', 'approved_by_id');
    }

    /**
     * Get the MD user who received the report.
     */
    public function mdUser()
    {
        return $this->belongsTo(User::class, 'md_user_id');
    }

    /**
     * Get the department reports included in this compilation.
     */
    public function departmentReports()
    {
        return $this->belongsToMany(DepartmentReport::class, 'compiled_report_department_report')
            ->withTimestamps();
    }

    /**
     * Get the distributions for this compiled report.
     */
    public function distributions()
    {
        return $this->morphMany(ReportDistribution::class, 'reportable');
    }

    /**
     * Get the annotations for this compiled report.
     */
    public function annotations()
    {
        return $this->morphMany(ReportAnnotation::class, 'reportable');
    }

    /**
     * Scope a query to only include reports for a specific branch.
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('compilation_date', [$from, $to]);
    }

    /**
     * Scope for reports pending approval.
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    /**
     * Scope for reports sent to MD.
     */
    public function scopeSentToMD($query)
    {
        return $query->whereIn('status', ['sent_to_md', 'reviewed_by_md']);
    }

    /**
     * Mark report as approved.
     */
    public function markAsApproved($employeeId, $employeeType = null)
    {
        // If no type provided, determine it from the ID
        if (!$employeeType) {
            $user = auth()->user();
            $employeeType = $user instanceof Employee ? Employee::class : User::class;
        }

        $this->update([
            'status' => 'approved',
            'approved_by_id' => $employeeId,
            'approved_by_type' => $employeeType,
            'approved_at' => now(),
        ]);
    }

    /**
     * Send report to MD.
     */
    public function sendToMD($mdUserId)
    {
        $this->update([
            'status' => 'sent_to_md',
            'md_user_id' => $mdUserId,
            'sent_to_md_at' => now(),
        ]);
    }

    /**
     * Mark as reviewed by MD.
     */
    public function markAsReviewedByMD($feedback = null)
    {
        $this->update([
            'status' => 'reviewed_by_md',
            'md_reviewed_at' => now(),
            'md_feedback' => $feedback,
        ]);
    }

    /**
     * Check if report is editable.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'pending_approval']);
    }

    /**
     * Check if report can be approved.
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending_approval';
    }

    /**
     * Check if report can be submitted for approval.
     */
    public function canBeSubmittedForApproval(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if report can be sent to MD.
     */
    public function canBeSentToMD(): bool
    {
        return $this->status !== 'sent_to_md';
    }

    /**
     * Get production reports included.
     */
    public function getProductionReports()
    {
        return $this->departmentReports()
            ->where('report_category', 'production')
            ->get();
    }

    /**
     * Get sales reports included.
     */
    public function getSalesReports()
    {
        return $this->departmentReports()
            ->where('report_category', 'sales')
            ->get();
    }

    /**
     * Get inventory reports included.
     */
    public function getInventoryReports()
    {
        return $this->departmentReports()
            ->where('report_category', 'inventory')
            ->get();
    }

    /**
     * Get accounting reports included.
     */
    public function getAccountingReports()
    {
        return $this->departmentReports()
            ->where('report_category', 'accounting')
            ->get();
    }
}

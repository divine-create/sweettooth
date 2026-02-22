<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DepartmentReport extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'department_id',
        'generated_by_id',
        'generated_by_type',
        'report_type',
        'report_category',
        'report_name',
        'report_date',
        'period_from',
        'period_to',
        'report_data',
        'summary_metrics',
        'charts_data',
        'system_data_hash',
        'system_data_version',
        'system_data_locked_at',
        'status',
        'reviewed_by_id',
        'reviewed_by_type',
        'reviewed_at',
        'review_notes',
        'export_format',
        'file_path',
    ];

    protected $casts = [
        'report_date' => 'date',
        'period_from' => 'date',
        'period_to' => 'date',
        'report_data' => 'array',
        'summary_metrics' => 'array',
        'charts_data' => 'array',
        'system_data_locked_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::updating(function (self $report) {
            if ($report->system_data_locked_at
                && $report->isDirty(['report_data', 'summary_metrics', 'charts_data'])) {
                throw new \RuntimeException('System data is locked and cannot be modified.');
            }
        });
    }

    /**
     * Get the branch that owns the report.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the department that owns the report.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the employee who generated the report.
     */
    public function generatedBy(): MorphTo
    {
        return $this->morphTo('generated_by', 'generated_by_type', 'generated_by_id');
    }

    /**
     * Get the employee who reviewed the report.
     */
    public function reviewedBy(): MorphTo
    {
        return $this->morphTo('reviewed_by', 'reviewed_by_type', 'reviewed_by_id');
    }

    /**
     * Get the distributions for this report.
     */
    public function distributions()
    {
        return $this->morphMany(ReportDistribution::class, 'reportable');
    }

    /**
     * Get the compiled reports that include this report.
     */
    public function compiledReports()
    {
        return $this->belongsToMany(CompiledReport::class, 'compiled_report_department_report')
            ->withTimestamps();
    }

    /**
     * Get the annotations for this report.
     */
    public function annotations()
    {
        return $this->morphMany(ReportAnnotation::class, 'reportable');
    }

    /**
     * Compute the system data hash for integrity checks.
     */
    public static function computeSystemDataHash(array $reportData, array $summaryMetrics, array $chartsData): string
    {
        $payload = [
            'report_data' => $reportData,
            'summary_metrics' => $summaryMetrics,
            'charts_data' => $chartsData,
        ];

        $payload = self::sortArrayRecursively($payload);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Verify stored system data hash.
     */
    public function systemDataHashIsValid(): bool
    {
        if (!$this->system_data_hash) {
            return false;
        }

        return hash_equals(
            $this->system_data_hash,
            self::computeSystemDataHash(
                $this->report_data ?? [],
                $this->summary_metrics ?? [],
                $this->charts_data ?? []
            )
        );
    }

    protected static function sortArrayRecursively(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = self::sortArrayRecursively($value);
            }
        }

        if (self::isAssoc($payload)) {
            ksort($payload);
        }

        return $payload;
    }

    protected static function isAssoc(array $payload): bool
    {
        if ($payload === []) {
            return false;
        }

        return array_keys($payload) !== range(0, count($payload) - 1);
    }

    /**
     * Scope a query to only include reports for a specific branch.
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope a query to only include reports for a specific department.
     */
    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope a query to filter by report category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('report_category', $category);
    }

    /**
     * Scope a query to filter by report type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('report_type', $type);
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
        return $query->whereBetween('report_date', [$from, $to]);
    }

    /**
     * Mark report as reviewed.
     */
    public function markAsReviewed($employeeId, $employeeType, $notes = null)
    {
        $this->update([
            'status' => 'reviewed',
            'reviewed_by_id' => $employeeId,
            'reviewed_by_type' => $employeeType,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    /**
     * Mark report as compiled.
     */
    public function markAsCompiled()
    {
        $this->update(['status' => 'compiled']);
    }

    /**
     * Mark report as sent to MD.
     */
    public function markAsSentToMD()
    {
        $this->update(['status' => 'sent_to_md']);
    }

    /**
     * Check if report is editable.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'pending_review']);
    }

    /**
     * Check if report can be reviewed.
     */
    public function canBeReviewed(): bool
    {
        return $this->status === 'pending_review';
    }

    /**
     * Check if report can be compiled.
     */
    public function canBeCompiled(): bool
    {
        return in_array($this->status, ['reviewed', 'compiled']);
    }
}

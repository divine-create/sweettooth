<?php

namespace App\Reports;

use App\Services\DepartmentProgressCalculator;
use Carbon\Carbon;

abstract class BaseReport
{
    protected string $title;
    protected array $metrics;
    protected DepartmentProgressCalculator $departmentCalculator;

    public function __construct()
    {
        $this->departmentCalculator = app(DepartmentProgressCalculator::class);
    }

    abstract public function generate(Carbon $date, ?int $departmentId = null): array;

    protected function formatCurrency(float $amount): string
    {
        return '$' . number_format($amount, 2);
    }

    protected function formatPercentage(float $value): string
    {
        return number_format($value, 2) . '%';
    }

    protected function calculateCompletionRate(Collection $productions): float
    {
        if ($productions->isEmpty()) return 0;

        $completed = $productions->where('status', 'completed')->count();
        return round(($completed / $productions->count()) * 100, 2);
    }

    protected function getReportMetadata($date, ?int $departmentId): array
    {
        return [
            'generated_at' => now(),
            'report_period' => $date instanceof Carbon ? $date->format('Y-m-d') : $date,
            'department_id' => $departmentId,
            'department_name' => $departmentId ? \App\Models\Department::find($departmentId)?->name : 'All Departments',
        ];
    }
}
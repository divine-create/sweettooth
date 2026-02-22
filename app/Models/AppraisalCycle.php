<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class AppraisalCycle extends Model
{
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'due_date',
        'status',
        'department_id'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'due_date' => 'date'
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }



    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId)
                    ->orWhereNull('department_id');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->where('status', '!=', 'completed');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'default_days_per_year',
        'requires_approval',
        'requires_document',
        'max_consecutive_days',
        'min_notice_days',
        'is_paid',
        'is_active',
        'color',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
        'requires_document' => 'boolean',
        'is_paid' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function leaveBalances()
    {
        return $this->hasMany(EmployeeLeaveBalance::class);
    }

    public function leaveApplications()
    {
        return $this->hasMany(LeaveApplication::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helpers
    public function getFormattedNameAttribute()
    {
        return $this->name.($this->is_paid ? ' (Paid)' : ' (Unpaid)');
    }
}

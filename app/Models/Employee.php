<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\RequiresApproval;

class Employee extends Authenticatable
{
    use HasRoles, HasUuids, Notifiable, TwoFactorAuthenticatable;

    use RequiresApproval;
    protected $guard_name = 'web';
    protected $table = 'users';

    protected $fillable = [
        'id', 'name', 'email', 'password', 'branch_id', 'is_active', 'last_accessed_branch_id',
        'employee_number', 'employee_id', 'department_id', 'manager_id', 'phone', 'hire_date',
        'employment_status', 'user_type',
        'address', 'date_of_birth', 'gender', 'nationality', 'emergency_contact_name',
        'emergency_contact_phone', 'termination_date', 'probation_end_date', 'shift_preference',
        'salary', 'hourly_rate', 'tax_id', 'bank_account', 'bank_name', 'allergies', 'profile_photo',
        'last_performance_review_date', 'performance_rating',
    ];

    public function getMorphClass()
    {
        return 'user';
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'branch_id' => 'string',
            'is_active' => 'boolean',
            'hire_date' => 'datetime',
        ];
    }

    public function initials(): string
    {
        $firstName = $this->name ?? ''; // Adjust field name if different (e.g., $this->name)

        $initials = mb_strtoupper(
            mb_substr($firstName, 0, 1)
        );

        return $initials ?: '??'; // Fallback if no names
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function subordinates()
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    // Leave Management Relationships
    public function leaveBalances()
    {
        return $this->hasMany(EmployeeLeaveBalance::class);
    }

    public function leaveApplications()
    {
        return $this->hasMany(LeaveApplication::class);
    }

    public function approvedLeaveApplications()
    {
        return $this->hasMany(LeaveApplication::class, 'approved_by');
    }

    public function leaveAllocations()
    {
        return $this->hasMany(EmployeeLeaveAllocation::class);
    }

    public function stepouts()
    {
        return $this->hasMany(EmployeeStepout::class);
    }

    public function clockIns()
    {
        return $this->hasMany(ClockIn::class);
    }
}

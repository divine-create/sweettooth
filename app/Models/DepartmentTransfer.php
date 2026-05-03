<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_number',
        'branch_id',
        'from_department_id',
        'to_department_id',
        'requested_by_id',
        'requested_by_type',
        'receiver_id',
        'receiver_type',
        'status',
        'approved_by_id',
        'approved_by_type',
        'approved_at',
        'dispatched_by_id',
        'dispatched_by_type',
        'dispatched_at',
        'received_by_id',
        'received_by_type',
        'received_at',
        'notes',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }
}

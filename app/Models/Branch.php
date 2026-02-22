<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'code',
        'location',
        'phone',
        'email',
        'description',
        'manager_user_id',
        'country',
        'state',
        'city',
        'postal_code',
        'timezone',
        'is_active',
        'enable_table_management',
        'table_management_settings',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'enable_table_management' => 'boolean',
        'table_management_settings' => 'array',
    ];

    /**
     * The attributes that should be mutated to dates.
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the manager of the branch.
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    /**
     * Get all tables for this branch.
     */
    public function tables()
    {
        return $this->hasMany(Table::class);
    }

    /**
     * Get sales production requests for this branch.
     */
    public function salesProductionRequests()
    {
        return $this->hasMany(SalesProductionRequest::class);
    }

    /**
     * Check if table management is enabled.
     */
    public function hasTableManagement(): bool
    {
        return $this->enable_table_management === true;
    }
}

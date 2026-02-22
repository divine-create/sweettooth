<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalEmployeeManagement extends Model
{
    public $table = 'global_employee_managements';

    protected $fillable = ['roles', 'permissions', 'staff_profiles', 'departments', 'shift_scheduling', 'pin_login'];

    protected $casts = [
        'roles' => 'array',
        'permissions' => 'array',
        'staff_profiles' => 'array',
        'departments' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

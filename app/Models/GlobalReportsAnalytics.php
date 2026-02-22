<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalReportsAnalytics extends Model
{
    protected $fillable = ['reports', 'custom_date_range', 'multi_select_delete', 'export'];

    protected $casts = [
        'reports' => 'array',
        'export' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalSecurityAccess extends Model
{
    protected $fillable = ['authentication', 'audit_logs', 'approval_required', 'data_isolation'];

    protected $casts = [
        'approval_required' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

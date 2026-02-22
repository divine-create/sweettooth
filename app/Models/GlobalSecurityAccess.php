<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalSecurityAccess extends Model
{
    protected $fillable = ['authentication', 'audit_logs', 'data_isolation'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

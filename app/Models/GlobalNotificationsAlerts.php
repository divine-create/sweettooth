<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalNotificationsAlerts extends Model
{
    protected $fillable = ['alerts', 'task_todo'];

    protected $casts = [
        'alerts' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

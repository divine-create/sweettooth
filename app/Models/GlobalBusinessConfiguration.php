<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalBusinessConfiguration extends Model
{
    protected $fillable = ['company_name', 'logo_upload', 'contact_details', 'auto_backup', 'backup_period', 'backup_interval', 'appearance_settings'];

    protected $casts = [
        'contact_details' => 'array',
        'auto_backup' => 'boolean',
        'backup_interval' => 'integer',
        'backup_period' => 'string',
        'appearance_settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

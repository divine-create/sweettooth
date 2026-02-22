<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingPostingFailure extends Model
{
    protected $fillable = [
        'reference_type',
        'reference_id',
        'entry_type',
        'error_message',
        'status',
        'context',
        'last_seen_at',
    ];

    protected $casts = [
        'context' => 'array',
        'last_seen_at' => 'datetime',
    ];
}


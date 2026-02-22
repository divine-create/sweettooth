<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalPosConfiguration extends Model
{
    protected $fillable = ['pos_interface', 'payment_modes', 'receipt_template', 'sales_returns', 'offline_mode', 'online_shop_sync'];

    protected $casts = [
        'payment_modes' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

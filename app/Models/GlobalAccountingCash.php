<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalAccountingCash extends Model
{
    protected $fillable = ['expenses_categories', 'cash_bank', 'profit_loss_reports', 'accounting_entries'];

    protected $casts = [
        'expenses_categories' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

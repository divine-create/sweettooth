<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalCurrencyLocalization extends Model
{
    protected $fillable = ['multi_currency', 'primary_currency', 'primary_currency_exchange_rate', 'currency_list', 'multi_tax', 'default_language', 'language_options', 'date_format', 'units_of_measure'];

    protected $casts = [
        'currency_list' => 'array',
        'language_options' => 'array',
        'units_of_measure' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

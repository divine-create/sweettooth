<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalInventoryManagement extends Model
{
    public $table = 'global_inventory_managements';

    protected $fillable = ['categories', 'brands', 'products', 'multi_variant', 'stock_adjustment', 'purchase_returns', 'supplier_management', 'low_stock_alert', 'expiry_tracking', 'import_csv'];

    protected $casts = [
        'categories' => 'array',
        'brands' => 'array',
        'products' => 'array',
        'supplier_management' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalCustomerSupplierManagement extends Model
{
    public $table = 'global_customer_supplier_managements';

    protected $fillable = ['customers', 'suppliers', 'party_import'];

    protected $casts = [
        'customers' => 'array',
        'suppliers' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

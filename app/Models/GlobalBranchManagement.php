<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalBranchManagement extends Model
{
    protected $table = 'global_branch_management';

    protected $fillable = ['warehouse_add', 'branch_edit', 'branch_delete', 'branch_admin_assign', 'inter_branch_transfer', 'central_warehouse', 'branch_hours', 'saas_tenant'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

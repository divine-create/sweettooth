<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DepartmentCategory extends Model
{
    use HasUuids;

    public $fillable = ['name', 'description'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportAnnotation extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'reportable_id',
        'reportable_type',
        'author_id',
        'author_type',
        'body',
    ];

    /**
     * Get the reportable model (DepartmentReport or CompiledReport).
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the author for this annotation.
     */
    public function author(): MorphTo
    {
        return $this->morphTo('author', 'author_type', 'author_id');
    }
}

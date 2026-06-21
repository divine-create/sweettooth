<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-branch mapping of an accounting "key" (e.g. cogs, inventory, bank_main)
 * to a GL account. The posting engine resolves accounts through these mappings
 * instead of hard-coding account numbers, so accounts can be re-pointed via
 * configuration without code changes.
 */
class BranchAccountingDefault extends Model
{
    protected $fillable = [
        'branch_id',
        'key',
        'gl_account_id',
    ];

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(GlAccount::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}

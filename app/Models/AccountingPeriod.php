<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountingPeriod extends Model
{
    use SoftDeletes;

    protected $table = 'accounting_periods';

    protected $fillable = [
        'branch_id',
        'year',
        'month',
        'period_start',
        'period_end',
        'status',
        'closed_by_id',
        'closed_by_type',
        'closed_at',
        'closing_notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'closed_at' => 'datetime',
    ];

    // Relationships
    public function entries(): HasMany
    {
        return $this->hasMany(GlEntry::class, 'accounting_period_id');
    }

    public function closedBy()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeLocked($query)
    {
        return $query->where('status', 'locked');
    }

    public function scopeCurrent($query)
    {
        // period_start/period_end are cast to `date` (stored at midnight), so a
        // datetime comparison against now() excludes the whole last day of the
        // period (e.g. anything after 00:00 on the 30th falls outside a period
        // ending 2026-06-30). Compare on the calendar date so the period covers
        // its entire last day.
        $today = now()->toDateString();
        return $query
            ->whereDate('period_start', '<=', $today)
            ->whereDate('period_end', '>=', $today)
            ->where('status', 'open');
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByMonth($query, $month)
    {
        return $query->where('month', $month);
    }

    // Methods
    public function close(int $userId, ?string $notes = null): bool
    {
        if ($this->status !== 'open') {
            return false;
        }

        $this->status = 'closed';
        $this->closed_by_id = $userId;
        $this->closed_at = now();
        $this->closing_notes = $notes;
        $this->save();

        return true;
    }

    public function lock(): bool
    {
        if ($this->status !== 'closed') {
            return false;
        }

        $this->status = 'locked';
        $this->save();

        return true;
    }

    public function reopen(): bool
    {
        if ($this->status !== 'closed') {
            return false;
        }

        $this->status = 'open';
        $this->closed_by_id = null;
        $this->closed_at = null;
        $this->closing_notes = null;
        $this->save();

        return true;
    }

    public function getDisplayName(): string
    {
        $monthName = \Carbon\Carbon::createFromFormat('n', $this->month)->format('F');
        return "{$monthName} {$this->year}";
    }
    
    public function getNameAttribute(): string
    {
        return $this->getDisplayName();
    }
}

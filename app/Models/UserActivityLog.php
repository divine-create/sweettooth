<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivityLog extends Model
{
    use MassPrunable;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'branch_id',
        'session_id',
        'event_type',
        'module',
        'route_name',
        'url',
        'component_class',
        'action_name',
        'metadata',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    public function prunable(): Builder
    {
        $days = config('activity.retention_days', 90);

        return static::where('created_at', '<', now()->subDays($days));
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeByUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    public function scopeByEventType(Builder $query, string $type): Builder
    {
        return $query->where('event_type', $type);
    }

    public function scopeOnline(Builder $query, int $minutes = 15): Builder
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeNonDeveloper(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('user_id')
              ->orWhereNotIn('user_id', function ($sub) {
                  $sub->select('id')->from('users')->where('is_developer', true);
              });
        });
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getReadableComponentAttribute(): string
    {
        if (!$this->component_class) {
            return '';
        }

        $parts = explode('\\', $this->component_class);

        // Drop "App\Livewire\BranchDashboard" prefix and join the rest
        $parts = array_filter($parts, fn ($p) => !in_array($p, ['App', 'Livewire', 'BranchDashboard']));
        $parts = array_values($parts);

        return implode(' › ', array_map(fn ($p) => preg_replace('/([A-Z])/', ' $1', $p), $parts));
    }

    public function getReadableActionAttribute(): string
    {
        if (!$this->action_name) {
            return '';
        }

        return ucwords(str_replace(['_', '-'], ' ', $this->action_name));
    }

    public function getEventBadgeColorAttribute(): string
    {
        return match ($this->event_type) {
            'login'            => 'green',
            'logout'           => 'gray',
            'failed_login'     => 'red',
            'page_view'        => 'blue',
            'component_action' => 'indigo',
            default            => 'gray',
        };
    }
}

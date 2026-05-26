<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, HasUuids, Notifiable, TwoFactorAuthenticatable;

    /*
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'branch_id',
        'is_active',
        'last_accessed_branch_id',
        'employee_number',
        'employee_id',
        'department_id',
        'manager_id',
        'phone',
        'hire_date',
        'employment_status',
        'user_type',
        'address',
        'date_of_birth',
        'gender',
        'nationality',
        'emergency_contact_name',
        'emergency_contact_phone',
        'termination_date',
        'probation_end_date',
        'shift_preference',
        'salary',
        'hourly_rate',
        'tax_id',
        'bank_account',
        'allergies',
        'profile_photo',
        'last_performance_review_date',
        'performance_rating',
        'bank_name',
        'is_developer',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'branch_id' => 'string',
            'is_active' => 'boolean',
            'is_developer' => 'boolean',
            'hire_date' => 'datetime',
            'last_performance_review_date' => 'datetime',
        ];
    }

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'employee_number' => ['required', 'string', 'max:50', 'unique:users'],
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'is_active' => ['boolean'],
        ];
    }

    // Authorization scopes - CRITICAL for data filtering
    public function scopeForCurrentUser(Builder $query): Builder
    {
        if (is_super_admin()) {
            return $query; // See everything
        }

        $branchId = get_user_branch_id();
        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }

        return $query->whereRaw('1 = 0'); // No access
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_developer', false);
    }

    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->whereHas('roles', fn($q) => $q->where('name', $role));
    }

    // Relationships
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Get the branch this user last accessed
     */
    public function lastAccessedBranch()
    {
        return $this->belongsTo(Branch::class, 'last_accessed_branch_id');
    }

    /**
     * Get the department this user belongs to
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Get the manager of this user
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get subordinates of this user
     */
    public function subordinates()
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    /**
     * Appraisals received by this user (as employee)
     */
    public function appraisals()
    {
        return $this->hasMany(Appraisal::class, 'employee_id');
    }

    // Authorization methods
    public function canManageUser(User $targetUser): bool
    {
        // Super admins can manage anyone
        if (function_exists('is_super_admin') && is_super_admin()) {
            return true;
        }

        // Admins can manage users in their branch
        if ($this->hasRole('Admin') &&
            $targetUser->branch_id === $this->branch_id) {
            return true;
        }

        return false;
    }

    public function getAccessibleBranches()
    {
        if (function_exists('is_super_admin') && is_super_admin()) {
            return Branch::all();
        }

        return collect([$this->branch]);
    }

    public function canAccessBranch(?string $branchId): bool
    {
        if (!$branchId) {
            return false;
        }

        if (function_exists('is_super_admin') && is_super_admin()) {
            return true;
        }

        return $this->branch_id === $branchId;
    }

    public function getMorphClass()
    {
        return 'user';
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}

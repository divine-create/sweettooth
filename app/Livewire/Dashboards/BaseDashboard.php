<?php

namespace App\Livewire\Dashboards;

use App\Helpers\CleanError;
use App\Helpers\Settings;
use App\Services\CurrencyFormattingService;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Base class for all dashboards
 * Provides common functionality for all role-based dashboards
 */
abstract class BaseDashboard extends Component
{
    /**
     * Current branch ID
     */
    protected ?string $branchId = null;

    /**
     * Current user's role
     */
    protected ?string $roleId = null;

    /**
     * Current authenticated user
     */
    protected $user = null;

    /**
     * Cache duration in minutes
     */
    protected int $cacheDuration = 60;

    /**
     * Date range for metrics
     */
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    /**
     * Initialize dashboard
     */
    public function mount()
    {
        $this->branchId = current_branch_id();
        $this->user = auth()->user();
        $this->setDefaultDateRange();
    }

    /**
     * Set default date range to current month
     */
    protected function setDefaultDateRange(): void
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->toDateString();
        $this->dateTo = Carbon::now()->endOfMonth()->toDateString();
    }

    /**
     * Get current branch ID
     */
    public function getBranchId(): ?string
    {
        return $this->branchId ?? current_branch_id();
    }

    /**
     * Get user's primary role
     */
    public function getUserRole()
    {
        $user = auth()->user();
        if (!$user || !is_object($user)) {
            return null;
        }

        if (method_exists($user, 'roles')) {
            return $user->roles()->first();
        }

        return null;
    }

    /**
     * Get user's role name
     */
    public function getUserRoleName(): ?string
    {
        // Use cached user from mount if available, otherwise get from auth
        $user = $this->user ?? auth()->user();
        if (!$user || !is_object($user)) {
            return null;
        }

        // Try to get role names using Spatie's method
        if (method_exists($user, 'getRoleNames')) {
            $roleNames = $user->getRoleNames();
            if ($roleNames && $roleNames->isNotEmpty()) {
                return $roleNames->first();
            }
        }

        // Fallback to roles relationship - directly query if lazy-loaded
        if (method_exists($user, 'roles')) {
            $role = $user->roles()->first();
            if ($role) {
                return $role->name;
            }
        }

        // Last resort - check if roles are already loaded as collection
        if (isset($user->roles) && $user->roles instanceof \Illuminate\Database\Eloquent\Collection && $user->roles->isNotEmpty()) {
            return $user->roles->first()->name;
        }

        return null;
    }

    /**
     * Cache a value with dashboard-specific cache key
     */
    protected function cacheKey(string $key): string
    {
        return 'dashboard:' . class_basename($this) . ':' . $this->getBranchId() . ':' . $key;
    }

    /**
     * Get cached value or execute callback
     */
    protected function remember(string $key, callable $callback)
    {
        return Cache::remember(
            $this->cacheKey($key),
            now()->addMinutes($this->cacheDuration),
            $callback
        );
    }

    /**
     * Forget cache
     */
    protected function forget(string $key): void
    {
        Cache::forget($this->cacheKey($key));
    }

    /**
     * Forget all dashboard cache
     */
    protected function forgetAll(): void
    {
        Cache::flush();
    }

    /**
     * Get date range as Carbon dates
     */
    protected function getDateRange(): array
    {
        return [
            'from' => Carbon::parse($this->dateFrom)->startOfDay(),
            'to' => Carbon::parse($this->dateTo)->endOfDay(),
        ];
    }

    /**
     * Check if user has permission
     */
    protected function hasPermission(string $permission): bool
    {
        $user = auth()->user();
        if (!$user || !is_object($user)) {
            return false;
        }

        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo($permission);
        }

        return false;
    }

    /**
     * Check if user has role
     */
    protected function hasRole(string $role): bool
    {
        $user = auth()->user();
        if (!$user || !is_object($user)) {
            return false;
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole($role);
        }

        return false;
    }

    /**
     * Get user's role level (1-5)
     * Helps determine what features to show
     */
    protected function getRoleLevel(): int
    {
        $user = auth()->user();
        if (!$user || !is_object($user)) {
            return 0;
        }

        return \App\Services\SidebarVisibilityService::getRoleLevel($user);
    }

    /**
     * Format currency
     */
    protected function formatCurrency(float $amount): string
    {
        $service = new CurrencyFormattingService();
        return $service->format($amount);
    }

    /**
     * Get currency symbol
     */
    protected function getCurrencySymbol(string $currency): string
    {
        $service = new CurrencyFormattingService();
        return $service->getSymbol($currency);
    }

    /**
     * Get primary currency
     */
    protected function getPrimaryCurrency(): string
    {
        return Settings::currencyLocalization('primary_currency', 'NGN');
    }

    /**
     * Format percentage
     */
    protected function formatPercentage(float $value, int $decimals = 1): string
    {
        return number_format($value, $decimals) . '%';
    }

    /**
     * Refresh dashboard data
     */
    public function refresh(): void
    {
        $this->forgetAll();
        $this->dispatch('refresh');
    }

    /**
     * Handle error with user-friendly message
     */
    protected function handleError(string $operation, \Exception $e): void
    {
        $user = auth()->user();
        $userId = null;
        if ($user && is_object($user)) {
            $userId = $user->id;
        }
        
        \Log::error("Dashboard error in {$operation}: " . $e->getMessage(), [
            'user_id' => $userId,
            'branch_id' => $this->getBranchId(),
            'exception' => $e,
        ]);

        $message = "Failed to load {$operation}. Please try again.";
        session()->flash('error', $message);

        if (method_exists($this, 'toast')) {
            CleanError::show($message, $e, [
                'operation' => $operation,
                'branch_id' => $this->getBranchId(),
                'user_id' => $userId,
            ], $this);
        }
    }
}

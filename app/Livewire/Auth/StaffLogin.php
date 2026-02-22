<?php

namespace App\Livewire\Auth;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Features;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class StaffLogin extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('required|uuid|exists:branches,id')]
    public string $branch_id = '';

    public bool $remember = false;

    public function render()
    {
        return view('livewire.auth.staff-login', [
            'branches' => Branch::where('is_active', true)->get(),
        ]);
    }

    /**
     * Handle an incoming authentication request for staff (unified system).
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        $user = $this->validateCredentials();

        // Use unified web guard for authentication
        Auth::guard('web')->login($user, $this->remember);

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        // Store branch context in session for the user's login
        Session::put('branch_id', $this->branch_id);
        session()->put('current_branch_id', $this->branch_id);

        // Update user's last accessed branch
        $user->update(['last_accessed_branch_id' => $this->branch_id]);

        // Redirect to DashboardRouter which will route based on role
        $this->redirectIntended(
            default: route('branch-dashboard.dashboards.router', ['b_id' => $this->branch_id], absolute: false),
            navigate: true
        );
    }

    /**
     * Validate credentials against unified User model.
     */
    protected function validateCredentials(): User
    {
        // Attempt authentication using Laravel's built-in validation
        if (!Auth::guard('web')->attempt([
            'email' => $this->email,
            'password' => $this->password
        ], false)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Get the authenticated user
        $user = Auth::guard('web')->user();

        // Check if user is active
        if (!$user->is_active) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('Account is inactive'),
            ]);
        }

        // For non-super-admin users, validate branch access
        if (!is_super_admin() && $user->branch_id !== $this->branch_id) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'branch_id' => __('You do not have access to this branch'),
            ]);
        }

        return $user;
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email) . '|' . $this->branch_id . '|' . request()->ip());
    }
}

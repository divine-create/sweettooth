<?php

namespace App\Livewire\Auth;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered(($user = User::create($validated))));

        Auth::login($user);

        Session::regenerate();

        $this->redirect($this->getRedirectUrl($user), navigate: true);
    }

    /**
     * Get the redirect URL based on user type.
     */
    private function getRedirectUrl($user): string
    {
        // Super-admin (auth()->user()) - redirect to branch-dashboard with b_id
        if ($user) {
            $branch = Branch::where('id', $user->last_accessed_branch_id)
                ->where('is_active', 1)
                ->first();

            if (!$branch) {
                $branch = Branch::where('is_active', 1)
                    ->orderBy('created_at')
                    ->first();
            }

            if ($branch) {
                return route('branch-dashboard.index', ['b_id' => $branch->id], absolute: false);
            }
        }

        return route('branch-select', absolute: false);
    }
}

<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: $this->getRedirectUrl($user));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Get the redirect URL based on user type.
     */
    private function getRedirectUrl($user): string
    {
        // Super-admin (auth()->user()) - redirect to branch-dashboard with b_id
        if ($user) {
            $branch = \App\Models\Branch::where('id', $user->last_accessed_branch_id)
                ->where('is_active', 1)
                ->first();

            if (!$branch) {
                $branch = \App\Models\Branch::where('is_active', 1)
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

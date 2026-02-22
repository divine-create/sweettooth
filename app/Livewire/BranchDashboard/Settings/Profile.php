<?php

namespace App\Livewire\BranchDashboard\Settings;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public ?string $b_id = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->b_id = request()->query('b_id');
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
        // Redirect back to branch dashboard with b_id
        if ($this->b_id) {
            return route('branch-dashboard.index', ['b_id' => $this->b_id]);
        }

        // Fallback to branch selection
        return route('branch-select');
    }

    public function render()
    {
        return view('livewire.branch-dashboard.settings.profile');
    }
}
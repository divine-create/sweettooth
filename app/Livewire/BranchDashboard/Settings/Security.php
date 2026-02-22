<?php

namespace App\Livewire\BranchDashboard\Settings;

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class Security extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $delete_password = '';

    public ?string $b_id = null;

    public function mount(): void
    {
        $this->b_id = request()->query('b_id');
    }

    /**
     * Update the password for the current authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', PasswordRule::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');
            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');
        session()->flash('password_message', 'Password updated successfully.');
        $this->dispatch('password-updated');
    }

    /**
     * Delete the current authenticated user account.
     */
    public function deleteAccount(Logout $logout): void
    {
        $this->validate([
            'delete_password' => ['required', 'string', 'current_password'],
        ]);

        $user = Auth::user();

        // Ensure session is closed before deleting the user account.
        $logout();
        $user?->delete();

        $this->redirect('/', navigate: true);
    }

    public function render()
    {
        return view('livewire.branch-dashboard.settings.security');
    }
}

<?php

namespace App\Livewire\Auth;

use App\Livewire\Actions\Logout;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class VerifyEmail extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: $this->getRedirectUrl(), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    /**
     * Get the redirect URL based on user type.
     */
    private function getRedirectUrl(): string
    {
        $user = Auth::user();

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

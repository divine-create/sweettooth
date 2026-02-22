<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended($this->getRedirectUrl($user).'?verified=1');
        }

        $request->fulfill();

        return redirect()->intended($this->getRedirectUrl($user).'?verified=1');
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

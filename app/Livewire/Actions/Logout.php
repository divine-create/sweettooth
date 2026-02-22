<?php

namespace App\Livewire\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Logout
{
    /**
     * Handles unified web guard logout for all users
     */
    public function __invoke()
    {
        // Logout from web guard
        Auth::guard('web')->logout();

        // Destroy all session data
        Session::flush();

        // Regenerate token to prevent CSRF attacks
        Session::regenerateToken();

        return redirect('/')->with('message', 'Logged out successfully');
    }
}

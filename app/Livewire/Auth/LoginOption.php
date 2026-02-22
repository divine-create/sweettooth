<?php

namespace App\Livewire\Auth;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout("components.layouts.auth.card")]
class LoginOption extends Component
{
    public function render()
    {
        return view('livewire.auth.login-option');
    }
}

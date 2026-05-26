<?php

namespace App\Listeners;

use App\Services\UserActivityService;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        if (!$event->user instanceof \Illuminate\Database\Eloquent\Model) {
            return;
        }

        UserActivityService::trackAuth($event->user, 'login');
    }
}

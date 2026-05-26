<?php

namespace App\Listeners;

use App\Services\UserActivityService;
use Illuminate\Auth\Events\Logout;

class LogLogout
{
    public function handle(Logout $event): void
    {
        if (!$event->user instanceof \Illuminate\Database\Eloquent\Model) {
            return;
        }

        UserActivityService::trackAuth($event->user, 'logout');
    }
}

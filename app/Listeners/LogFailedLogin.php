<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\UserActivityService;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        // $event->user is null on failed attempts — resolve from credentials
        $resolved = $event->user ?? User::where('email', $event->credentials['email'] ?? '')->first();

        if (!$resolved instanceof \Illuminate\Database\Eloquent\Model) {
            return;
        }

        UserActivityService::trackAuth($resolved, 'failed_login', [
            'guard' => $event->guard,
        ]);
    }
}

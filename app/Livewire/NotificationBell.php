<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class NotificationBell extends Component
{
    public function markAllRead(): void
    {
        auth()->user()?->unreadNotifications->markAsRead();
    }

    public function markRead(string $id): void
    {
        auth()->user()?->notifications()->where('id', $id)->first()?->markAsRead();
    }

    public function render(): View
    {
        $user = auth()->user();

        $notifications = $user
            ? $user->notifications()->latest()->take(12)->get()
            : collect();

        $unreadCount = $user
            ? $user->unreadNotifications()->count()
            : 0;

        return view('livewire.notification-bell', compact('notifications', 'unreadCount'));
    }
}

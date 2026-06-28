<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class NotificationBell extends Component
{
    /** Unread count as of the last poll — used to detect newly-arrived notifications. */
    public int $lastUnreadCount = 0;

    public function mount(): void
    {
        // Seed the baseline so we don't chime for notifications that were already
        // unread when the page loaded.
        $this->lastUnreadCount = auth()->user()?->unreadNotifications()->count() ?? 0;
    }

    /**
     * Polled from the view. If the unread count has gone up since the last poll,
     * a new notification arrived — tell the browser to play the alert sound.
     */
    public function poll(): void
    {
        $count = auth()->user()?->unreadNotifications()->count() ?? 0;

        if ($count > $this->lastUnreadCount) {
            $this->dispatch('notification-received');
        }

        $this->lastUnreadCount = $count;
    }

    public function markAllRead(): void
    {
        auth()->user()?->unreadNotifications->markAsRead();
        $this->lastUnreadCount = 0;
    }

    public function markRead(string $id): void
    {
        auth()->user()?->notifications()->where('id', $id)->first()?->markAsRead();
        $this->lastUnreadCount = auth()->user()?->unreadNotifications()->count() ?? 0;
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

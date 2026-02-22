<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShiftReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public string $shiftType;

    public Carbon $scheduledTime;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $shiftType, Carbon $scheduledTime)
    {
        $this->shiftType = $shiftType;
        $this->scheduledTime = $scheduledTime;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $shiftType = ucfirst(str_replace('_', ' ', $this->shiftType));

        return (new MailMessage)
            ->subject("📅 {$shiftType} Shift Reminder")
            ->greeting("Hi {$notifiable->name},")
            ->line("This is a friendly reminder about your upcoming **{$shiftType} shift**.")
            ->line("**Scheduled Time:** {$this->scheduledTime->format('l, F j, Y g:i A')}")
            ->line("**Time Window:** {$this->getTimeWindowText()}")
            ->action('View Dashboard', route('branch-dashboard.select_shift'))
            ->line('Please ensure you arrive on time and clock in within the allowed time window.')
            ->salutation('Best regards, Management Team');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'shift_reminder',
            'shift_type' => $this->shiftType,
            'scheduled_time' => $this->scheduledTime->toDateTimeString(),
            'time_window' => $this->getTimeWindowText(),
            'message' => "Reminder: Your {$this->shiftType} shift is scheduled for {$this->scheduledTime->format('g:i A')}",
            'action_url' => route('branch-dashboard.select_shift'),
            'action_text' => 'View Dashboard',
        ];
    }

    private function getTimeWindowText(): string
    {
        return match ($this->shiftType) {
            'morning' => '6:00 AM - 12:00 PM',
            'afternoon' => '12:00 PM - 8:00 PM',
            'full_time' => 'No specific time restrictions',
            default => 'Check your schedule'
        };
    }
}

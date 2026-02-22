<?php

namespace App\Notifications;

use App\Models\Shift;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShiftEndingWarning extends Notification implements ShouldQueue
{
    use Queueable;

    public Shift $shift;

    public int $minutesRemaining;

    /**
     * Create a new notification instance.
     */
    public function __construct(Shift $shift, int $minutesRemaining)
    {
        $this->shift = $shift;
        $this->minutesRemaining = $minutesRemaining;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $shiftType = ucfirst(str_replace('_', ' ', $this->shift->shift_type));

        return (new MailMessage)
            ->subject("⚠️ {$shiftType} Shift Ending Soon")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your {$shiftType} shift will end in **{$this->minutesRemaining} minutes**.")
            ->line("**Clocked In:** {$this->shift->clock_in->format('g:i A')}")
            ->line('**Expected End:** '.$this->shift->configuration?->end_time ?? 'Based on shift duration')
            ->action('Clock Out Now', route('branch-dashboard.select_shift', ['b_id' => $this->shift->branch_id]))
            ->line('Remember to complete any pending work before your shift ends.')
            ->salutation('Best regards, Management Team');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'shift_ending_warning',
            'shift_id' => $this->shift->id,
            'shift_type' => $this->shift->shift_type,
            'minutes_remaining' => $this->minutesRemaining,
            'clock_in_time' => $this->shift->clock_in->toDateTimeString(),
            'message' => "Your {$this->shift->shift_type} shift will end in {$this->minutesRemaining} minutes",
            'action_url' => route('branch-dashboard.select_shift', ['b_id' => $this->shift->branch_id]),
            'action_text' => 'Clock Out',
        ];
    }
}

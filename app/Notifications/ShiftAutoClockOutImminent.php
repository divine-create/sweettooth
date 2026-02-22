<?php

namespace App\Notifications;

use App\Models\Shift;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShiftAutoClockOutImminent extends Notification implements ShouldQueue
{
    use Queueable;

    public Shift $shift;

    public int $minutesUntilAutoClockOut;

    /**
     * Create a new notification instance.
     */
    public function __construct(Shift $shift, int $minutesUntilAutoClockOut)
    {
        $this->shift = $shift;
        $this->minutesUntilAutoClockOut = $minutesUntilAutoClockOut;
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
            ->subject("🚨 Auto Clock Out Warning - {$shiftType} Shift")
            ->greeting("Hi {$notifiable->name},")
            ->line("⚠️ **URGENT**: Your {$shiftType} shift will be automatically closed in **{$this->minutesUntilAutoClockOut} minutes**!")
            ->line("**Clocked In:** {$this->shift->clock_in->format('g:i A')}")
            ->line('**Auto Clock Out:** '.now()->addMinutes($this->minutesUntilAutoClockOut)->format('g:i A'))
            ->action('Clock Out Now', route('branch-dashboard.select_shift', ['b_id' => $this->shift->branch_id]))
            ->line('**Important:** Auto clock out will preserve your worked time but may require additional approval.')
            ->line('Please clock out manually to avoid any issues.')
            ->salutation('Best regards, Management Team');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'auto_clock_out_warning',
            'shift_id' => $this->shift->id,
            'shift_type' => $this->shift->shift_type,
            'minutes_until_auto_clock' => $this->minutesUntilAutoClockOut,
            'clock_in_time' => $this->shift->clock_in->toDateTimeString(),
            'auto_clock_time' => now()->addMinutes($this->minutesUntilAutoClockOut)->toDateTimeString(),
            'message' => "Your {$this->shift->shift_type} shift will auto clock out in {$this->minutesUntilAutoClockOut} minutes",
            'action_url' => route('branch-dashboard.select_shift', ['b_id' => $this->shift->branch_id]),
            'action_text' => 'Clock Out Now',
            'urgent' => true,
        ];
    }
}

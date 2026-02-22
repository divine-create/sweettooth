<?php

namespace App\Notifications;

use App\Models\Shift;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShiftCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public Shift $shift;

    public string $completionReason;

    /**
     * Create a new notification instance.
     */
    public function __construct(Shift $shift, string $completionReason)
    {
        $this->shift = $shift;
        $this->completionReason = $completionReason;
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
        $shiftType = ucfirst(str_replace('_', ' ', $this->shift->shift_type));
        $totalHours = $this->shift->clock_in && $this->shift->clock_out
            ? $this->shift->clock_in->diffInHours($this->shift->clock_out)
            : 0;
        $totalMinutes = $this->shift->clock_in && $this->shift->clock_out
            ? $this->shift->clock_in->diffInMinutes($this->shift->clock_out) % 60
            : 0;

        $completionText = $this->completionReason === 'auto' ? 'automatically closed' : 'successfully completed';

        return (new MailMessage)
            ->subject("✅ {$shiftType} Shift {$completionText}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your {$shiftType} shift has been {$completionText}.")
            ->line("**Clocked In:** {$this->shift->clock_in->format('g:i A')}")
            ->line("**Clocked Out:** {$this->shift->clock_out->format('g:i A')}")
            ->line("**Total Time:** {$totalHours}h {$totalMinutes}m")
            ->when($this->completionReason === 'auto', function ($mail) {
                $mail->line('**Note:** This shift was automatically closed due to exceeding the configured time limit.');
            })
            ->action('View Dashboard', route('branch-dashboard.select_shift'))
            ->line('Thank you for your hard work!')
            ->salutation('Best regards, Management Team');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $totalHours = $this->shift->clock_in && $this->shift->clock_out
            ? $this->shift->clock_in->diffInHours($this->shift->clock_out)
            : 0;
        $totalMinutes = $this->shift->clock_in && $this->shift->clock_out
            ? $this->shift->clock_in->diffInMinutes($this->shift->clock_out) % 60
            : 0;

        return [
            'type' => 'shift_completed',
            'shift_id' => $this->shift->id,
            'shift_type' => $this->shift->shift_type,
            'completion_reason' => $this->completionReason,
            'clock_in_time' => $this->shift->clock_in?->toDateTimeString(),
            'clock_out_time' => $this->shift->clock_out?->toDateTimeString(),
            'total_hours' => $totalHours,
            'total_minutes' => $totalMinutes,
            'message' => "Your {$this->shift->shift_type} shift has been completed ({$totalHours}h {$totalMinutes}m)",
            'action_url' => route('branch-dashboard.select_shift'),
            'action_text' => 'View Dashboard',
        ];
    }
}

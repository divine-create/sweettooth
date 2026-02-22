<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BackupCompletedNotification extends Notification
{
    use Queueable;

    /**
     * Backup result data
     */
    protected $result;

    /**
     * Whether the backup was successful
     */
    protected $success;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $result, bool $success)
    {
        $this->result = $result;
        $this->success = $success;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        if ($this->success) {
            return (new MailMessage)
                ->subject('Database Backup Completed Successfully')
                ->line('Your database backup has been completed successfully.')
                ->line('Filename: '.($this->result['filename'] ?? 'N/A'))
                ->line('Size: '.($this->result['size'] ?? 'N/A'))
                ->line('Thank you for using our application!');
        }

        return (new MailMessage)
            ->subject('Database Backup Failed')
            ->error()
            ->line('Your database backup has failed.')
            ->line('Error: '.($this->result['message'] ?? 'Unknown error'))
            ->line('Please check the logs for more details.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'database_backup',
            'success' => $this->success,
            'message' => $this->success
                ? 'Database backup completed successfully'
                : 'Database backup failed: '.($this->result['message'] ?? 'Unknown error'),
            'filename' => $this->result['filename'] ?? null,
            'size' => $this->result['size'] ?? null,
            'path' => $this->result['path'] ?? null,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}

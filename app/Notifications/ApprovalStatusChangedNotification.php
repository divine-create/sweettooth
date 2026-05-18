<?php

namespace App\Notifications;

use App\Models\ApprovalAuditRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ApprovalAuditRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approved = $this->request->status === 'approved';
        $action   = $this->humanAction();
        $approverName = $this->request->approver?->name ?? 'An administrator';

        $mail = (new MailMessage)
            ->subject($approved ? "Request Approved — {$action}" : "Request Denied — {$action}")
            ->greeting("Hi {$notifiable->name},")
            ->line("{$approverName} has " . ($approved ? 'approved' : 'denied') . " your request.")
            ->line("**Request type:** {$action}");

        if (! $approved && $this->request->rejection_comment) {
            $mail->line("**Reason:** {$this->request->rejection_comment}");
        }

        return $mail
            ->action('View Dashboard', url('/branch-dashboard'))
            ->salutation('Best regards, ' . config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        $approved = $this->request->status === 'approved';

        return [
            'type'         => 'approval_status_changed',
            'request_id'   => $this->request->id,
            'status'       => $this->request->status,
            'action_label' => $this->humanAction(),
            'approver_name' => $this->request->approver?->name ?? 'Unknown',
            'rejection_comment' => $this->request->rejection_comment,
            'message'      => ($approved ? 'Approved: ' : 'Denied: ') . $this->humanAction(),
            'action_url'   => url('/branch-dashboard'),
        ];
    }

    private function humanAction(): string
    {
        $raw = explode(':', $this->request->action)[0] ?? $this->request->action;

        return match ($raw) {
            'product:create'  => 'Create Product',
            'product:update'  => 'Update Product',
            'product:delete'  => 'Delete Product',
            'recipe:create'   => 'Create Recipe',
            'recipe:update'   => 'Update Recipe',
            'stock_adjustment' => 'Stock Adjustment',
            'callback:inventory' => 'Inventory Callback',
            'callback:production' => 'Production Callback',
            default => ucwords(str_replace([':', '_'], [' — ', ' '], $raw)),
        };
    }
}

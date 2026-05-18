<?php

namespace App\Notifications;

use App\Models\ApprovalAuditRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ApprovalAuditRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $action = $this->humanAction();
        $requesterName = $this->request->requester?->name ?? 'Someone';

        return (new MailMessage)
            ->subject("Action Required: Approval Needed — {$action}")
            ->greeting("Hi {$notifiable->name},")
            ->line("{$requesterName} has submitted a request that requires your approval.")
            ->line("**Request type:** {$action}")
            ->when($this->request->description, fn ($m) => $m->line("**Note:** {$this->request->description}"))
            ->action('Review Request', url('/branch-dashboard'))
            ->line('Please review and approve or deny this request at your earliest convenience.')
            ->salutation('Best regards, ' . config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'approval_request',
            'request_id'     => $this->request->id,
            'action'         => $this->request->action,
            'action_label'   => $this->humanAction(),
            'requester_name' => $this->request->requester?->name ?? 'Unknown',
            'description'    => $this->request->description,
            'message'        => 'New approval request: ' . $this->humanAction(),
            'action_url'     => url('/branch-dashboard'),
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

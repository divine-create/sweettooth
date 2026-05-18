<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GlPostingFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $entityType,
        public string $entityId,
        public string $error,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = $this->entityLabel();

        return (new MailMessage)
            ->subject("GL Posting Failed — {$label}")
            ->greeting("Hi {$notifiable->name},")
            ->line("A General Ledger posting has failed and requires your attention.")
            ->line("**Entity:** {$label} (ID: {$this->entityId})")
            ->line("**Error:** {$this->error}")
            ->action('Open Posting Monitor', url('/branch-dashboard/accounting/posting-monitor'))
            ->line('You can retry the posting from the Posting Status Monitor.')
            ->salutation('Best regards, ' . config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'gl_posting_failed',
            'entity_type' => $this->entityType,
            'entity_id'   => $this->entityId,
            'entity_label' => $this->entityLabel(),
            'error'       => $this->error,
            'message'     => "GL posting failed for {$this->entityLabel()} #{$this->entityId}",
            'action_url'  => url('/branch-dashboard/accounting/posting-monitor'),
        ];
    }

    private function entityLabel(): string
    {
        return match ($this->entityType) {
            'sale'                 => 'Sale',
            'purchase'             => 'Purchase',
            'payment'              => 'Payment',
            'purchase_payment'     => 'Purchase Payment',
            'inventory_adjustment' => 'Inventory Adjustment',
            'account_transfer'     => 'Account Transfer',
            'expense_claim'        => 'Expense Claim',
            'credit_note'          => 'Credit Note',
            'debit_note'           => 'Debit Note',
            'production_order'     => 'Production Order',
            'payroll'              => 'Payroll',
            'tax_payment'          => 'Tax Payment',
            'fixed_asset'          => 'Fixed Asset',
            default                => ucwords(str_replace('_', ' ', $this->entityType)),
        };
    }
}

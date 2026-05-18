<?php

namespace App\Notifications;

use App\Enums\SalesProductionRequestStatus;
use App\Models\SalesProductionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductionRequestStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SalesProductionRequest $request,
        public SalesProductionRequestStatus $from,
        public SalesProductionRequestStatus $to,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = $this->statusLabel($this->to);
        $products = $this->request->items()->with('product')->get()
            ->pluck('product.name')->filter()->join(', ');

        $mail = (new MailMessage)
            ->subject("Production Request {$label} — #{$this->request->id}")
            ->greeting("Hi {$notifiable->name},")
            ->line("A production request has been updated.")
            ->line("**Status:** {$this->statusLabel($this->from)} → **{$label}**")
            ->when($products, fn ($m) => $m->line("**Products:** {$products}"));

        if (in_array($this->to, [SalesProductionRequestStatus::REJECTED, SalesProductionRequestStatus::CANCELLED])) {
            $reason = $this->request->rejection_reason ?? $this->request->cancellation_reason ?? null;
            if ($reason) {
                $mail->line("**Reason:** {$reason}");
            }
        }

        return $mail
            ->action('View Request', url('/branch-dashboard'))
            ->salutation('Best regards, ' . config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'production_request_status',
            'request_id'  => $this->request->id,
            'from_status' => $this->from->value,
            'to_status'   => $this->to->value,
            'status_label' => $this->statusLabel($this->to),
            'branch_id'   => $this->request->branch_id,
            'message'     => "Production request #{$this->request->id} is now {$this->statusLabel($this->to)}",
            'action_url'  => url('/branch-dashboard'),
        ];
    }

    private function statusLabel(SalesProductionRequestStatus $status): string
    {
        return match ($status) {
            SalesProductionRequestStatus::PENDING               => 'Pending',
            SalesProductionRequestStatus::APPROVED_BY_PRODUCTION => 'Approved by Production',
            SalesProductionRequestStatus::MATERIALS_REQUESTED   => 'Materials Requested',
            SalesProductionRequestStatus::MATERIALS_APPROVED    => 'Materials Approved',
            SalesProductionRequestStatus::PROCESSING            => 'In Progress',
            SalesProductionRequestStatus::COMPLETED             => 'Completed',
            SalesProductionRequestStatus::DISPATCHED            => 'Dispatched',
            SalesProductionRequestStatus::RECEIVED_BY_SALES     => 'Received by Sales',
            SalesProductionRequestStatus::REJECTED              => 'Rejected',
            SalesProductionRequestStatus::CANCELLED             => 'Cancelled',
        };
    }
}

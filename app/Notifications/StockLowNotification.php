<?php

namespace App\Notifications;

use App\Models\ProductStock;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StockLowNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ProductStock $productStock) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $product = $this->productStock->product;
        $name    = $product?->name ?? 'Unknown product';
        $current = number_format((float) $this->productStock->closing_quantity, 2);
        $reorder = number_format((float) ($product?->reorder_level ?? 0), 2);
        $uom     = $product?->uom?->symbol ?? '';

        return (new MailMessage)
            ->subject("Low Stock Alert — {$name}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Stock for **{$name}** has fallen below the reorder level.")
            ->line("**Current stock:** {$current} {$uom}")
            ->line("**Reorder level:** {$reorder} {$uom}")
            ->action('Go to Inventory', url('/branch-dashboard/inventory'))
            ->line('Please arrange a restock at your earliest convenience.')
            ->salutation('Best regards, ' . config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        $product = $this->productStock->product;

        return [
            'type'             => 'stock_low',
            'product_stock_id' => $this->productStock->id,
            'product_id'       => $product?->id,
            'product_name'     => $product?->name ?? 'Unknown',
            'closing_quantity'  => (float) $this->productStock->closing_quantity,
            'reorder_level'    => (float) ($product?->reorder_level ?? 0),
            'branch_id'        => $this->productStock->branch_id,
            'message'          => 'Low stock: ' . ($product?->name ?? 'Unknown product'),
            'action_url'       => url('/branch-dashboard/inventory'),
        ];
    }
}

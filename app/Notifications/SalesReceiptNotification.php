<?php

namespace App\Notifications;

use App\Helpers\Settings;
use App\Models\Receipt;
use App\Services\CurrencyFormattingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SalesReceiptNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Receipt $receipt;

    /**
     * Create a new notification instance.
     */
    public function __construct(Receipt $receipt)
    {
        $this->receipt = $receipt;
    }

    /**
     * Get the notification's delivery channels.
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
        $currencyService = new CurrencyFormattingService;
        $currency = Settings::currencyLocalization('primary_currency', 'NGN');
        $symbol = $currencyService->getSymbol($currency);
        $businessName = Settings::businessConfiguration('business_name', config('app.name'));

        $receiptItems = $this->receipt->sale->saleItems()
            ->with('product')
            ->get()
            ->map(fn ($item) => [
                'name' => $item->product->name ?? 'Product',
                'qty' => number_format($item->quantity, 2),
                'unit_price' => $currencyService->format($item->unit_price),
                'total' => $currencyService->format($item->total),
            ])
            ->toArray();

        return (new MailMessage)
            ->subject("Sales Receipt #{$this->receipt->receipt_number}")
            ->greeting('Thank you for your purchase!')
            ->line('Dear Customer,')
            ->line('Please find your receipt details below:')
            ->line('')
            ->line('**Receipt Information**')
            ->line("Receipt Number: {$this->receipt->receipt_number}")
            ->line("Date: {$this->receipt->created_at->format('Y-m-d H:i:s')}")
            ->line('')
            ->line('**Items Purchased**')
            ->with([
                'receiptItems' => $receiptItems,
                'symbol' => $symbol,
                'receipt' => $this->receipt,
                'currencyService' => $currencyService,
            ])
            ->line('')
            ->line('**Order Summary**')
            ->line("Subtotal: {$symbol} {$currencyService->formatAmount($this->receipt->subtotal)}")
            ->when($this->receipt->discount > 0, function (MailMessage $message) use ($symbol, $currencyService) {
                return $message->line("Discount: {$symbol} {$currencyService->formatAmount($this->receipt->discount)}");
            })
            ->when($this->receipt->tax > 0, function (MailMessage $message) use ($symbol, $currencyService) {
                return $message->line("Tax: {$symbol} {$currencyService->formatAmount($this->receipt->tax)}");
            })
            ->line("**Total: {$symbol} {$currencyService->formatAmount($this->receipt->total)}**")
            ->line('')
            ->line('**Payment Method**')
            ->with(['payments' => $this->receipt->payments])
            ->when($this->receipt->change_due > 0, function (MailMessage $message) use ($symbol, $currencyService) {
                return $message->line("Change Due: {$symbol} {$currencyService->formatAmount($this->receipt->change_due)}");
            })
            ->line('')
            ->line("Thank you for shopping with {$businessName}!")
            ->salutation('Regards,')
            ->line("{$businessName}");
    }
}

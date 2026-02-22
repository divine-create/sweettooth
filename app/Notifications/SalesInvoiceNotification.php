<?php

namespace App\Notifications;

use App\Helpers\Settings;
use App\Models\Sale;
use App\Services\CurrencyFormattingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SalesInvoiceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Sale $sale;

    protected ?string $message = null;

    /**
     * Create a new notification instance.
     */
    public function __construct(Sale $sale, ?string $message = null)
    {
        $this->sale = $sale;
        $this->message = $message;
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
        $businessEmail = Settings::businessConfiguration('business_email', config('mail.from.address'));
        $businessPhone = Settings::businessConfiguration('business_phone', '');

        $saleItems = $this->sale->saleItems()
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
            ->subject("Invoice #{$this->sale->receipt_number}")
            ->greeting('Invoice for Your Purchase')
            ->line('Dear Customer,')
            ->line($this->message ?? 'Please find your invoice details below:')
            ->line('')
            ->line('**Invoice Information**')
            ->line("Invoice Number: {$this->sale->receipt_number}")
            ->line("Date: {$this->sale->created_at->format('Y-m-d H:i:s')}")
            ->line('Order Type: '.ucfirst(str_replace('-', ' ', $this->sale->order_type ?? 'dine-in')))
            ->line('')
            ->line('**Items**')
            ->with([
                'saleItems' => $saleItems,
                'symbol' => $symbol,
                'sale' => $this->sale,
                'currencyService' => $currencyService,
            ])
            ->line('')
            ->line('**Amount Details**')
            ->line("Subtotal: {$symbol} {$currencyService->formatAmount($this->sale->subtotal)}")
            ->when($this->sale->discount > 0, function (MailMessage $message) use ($symbol, $currencyService) {
                return $message->line("Discount: -{$symbol} {$currencyService->formatAmount($this->sale->discount)}");
            })
            ->when($this->sale->tax > 0, function (MailMessage $message) use ($symbol, $currencyService) {
                return $message->line("Tax: {$symbol} {$currencyService->formatAmount($this->sale->tax)}");
            })
            ->line("**Total Amount: {$symbol} {$currencyService->formatAmount($this->sale->total)}**")
            ->line('')
            ->line('**Status**')
            ->line('Payment Status: '.ucfirst(str_replace('_', ' ', $this->sale->payment_status ?? 'pending')))
            ->line('Fulfillment Status: '.ucfirst(str_replace('_', ' ', $this->sale->fulfillment_status ?? 'pending')))
            ->line('')
            ->line('**Business Information**')
            ->line($businessName)
            ->when(! empty($businessEmail), fn (MailMessage $msg) => $msg->line("Email: {$businessEmail}"))
            ->when(! empty($businessPhone), fn (MailMessage $msg) => $msg->line("Phone: {$businessPhone}"))
            ->line('')
            ->line('Thank you for your business!')
            ->salutation('Regards,')
            ->line($businessName);
    }
}

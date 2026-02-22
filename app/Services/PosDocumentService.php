<?php

namespace App\Services;

use App\Helpers\Settings;
use App\Models\Receipt;
use App\Models\Sale;

class PosDocumentService
{
    protected CurrencyFormattingService $currencyService;

    public function __construct()
    {
        $this->currencyService = new CurrencyFormattingService();
    }

    /**
     * Generate a branded receipt HTML for printing
     */
    public function generateBrandedReceiptHtml(Sale $sale): string
    {
        $businessName = Settings::businessConfiguration('business_name', config('app.name'));
        $businessAddress = Settings::businessConfiguration('business_address', '');
        $businessPhone = Settings::businessConfiguration('business_phone', '');
        $businessEmail = Settings::businessConfiguration('business_email', '');
        $taxId = Settings::businessConfiguration('tax_id', '');

        $currency = Settings::currencyLocalization('primary_currency', 'NGN');
        $symbol = $this->currencyService->getSymbol($currency);

        $items = $sale->saleItems()
            ->with('product')
            ->get()
            ->map(fn ($item) => [
                'name' => $item->product->name ?? 'Product',
                'qty' => number_format($item->quantity, 2),
                'unit_price' => $this->currencyService->formatAmount($item->unit_price),
                'total' => $this->currencyService->formatAmount($item->total),
            ])
            ->toArray();

        $itemsHtml = '';
        foreach ($items as $item) {
            $itemsHtml .= "
            <tr>
                <td style=\"padding: 4px 0;\">{$item['name']}</td>
                <td style=\"text-align: right; padding: 4px 8px;\">{$item['qty']}</td>
                <td style=\"text-align: right; padding: 4px 8px;\">{$symbol}{$item['unit_price']}</td>
                <td style=\"text-align: right; padding: 4px 0;\">{$symbol}{$item['total']}</td>
            </tr>";
        }

        $subtotal = $this->currencyService->formatAmount($sale->subtotal);
        $discount = $this->currencyService->formatAmount($sale->discount);
        $tax = $this->currencyService->formatAmount($sale->tax);
        $total = $this->currencyService->formatAmount($sale->total);
        $date = $sale->created_at->format('Y-m-d H:i:s');
        $orderType = ucfirst(str_replace('-', ' ', $sale->order_type ?? 'dine-in'));

        $discountRow = $sale->discount > 0 ? "
            <tr>
                <td colspan=\"3\" style=\"text-align: right; padding: 4px 8px;\">Discount:</td>
                <td style=\"text-align: right; padding: 4px 0;\">-{$symbol}{$discount}</td>
            </tr>" : '';

        $taxRow = $sale->tax > 0 ? "
            <tr>
                <td colspan=\"3\" style=\"text-align: right; padding: 4px 8px;\">Tax:</td>
                <td style=\"text-align: right; padding: 4px 0;\">{$symbol}{$tax}</td>
            </tr>" : '';

        $receiptNumber = $sale->receipt_number ?? 'N/A';

        return <<<HTML
        <div style="font-family: monospace; max-width: 400px; margin: 0 auto; padding: 20px; background: white;">
            <div style="text-align: center; margin-bottom: 16px;">
                <h2 style="margin: 0; font-size: 18px;">{$businessName}</h2>
                {$businessAddress}
                {$businessPhone}
                {$businessEmail}
                {$taxId}
            </div>
            
            <hr style="border: none; border-top: 1px dashed #333; margin: 12px 0;">
            
            <div style="text-align: center; font-size: 14px; margin-bottom: 12px;">
                SALES RECEIPT
            </div>
            
            <div style="font-size: 12px; margin-bottom: 12px;">
                <div style="display: flex; justify-content: space-between;">
                    <span>Receipt #:</span>
                    <span>{$receiptNumber}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Date:</span>
                    <span>{$date}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Order Type:</span>
                    <span>{$orderType}</span>
                </div>
            </div>
            
            <hr style="border: none; border-top: 1px dashed #333; margin: 12px 0;">
            
            <table style="width: 100%; font-size: 12px; margin-bottom: 12px;">
                <thead>
                    <tr style="border-bottom: 1px solid #333;">
                        <th style="text-align: left; padding: 4px 0;">Item</th>
                        <th style="text-align: right; padding: 4px 8px;">Qty</th>
                        <th style="text-align: right; padding: 4px 8px;">Price</th>
                        <th style="text-align: right; padding: 4px 0;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    {$itemsHtml}
                </tbody>
            </table>
            
            <hr style="border: none; border-top: 1px dashed #333; margin: 12px 0;">
            
            <table style="width: 100%; font-size: 12px; margin-bottom: 12px;">
                <tr>
                    <td colspan="3" style="text-align: right; padding: 4px 8px;">Subtotal:</td>
                    <td style="text-align: right; padding: 4px 0;">{$symbol}{$subtotal}</td>
                </tr>
                {$discountRow}
                {$taxRow}
                <tr style="border-top: 1px solid #333; border-bottom: 1px solid #333; font-weight: bold;">
                    <td colspan="3" style="text-align: right; padding: 4px 8px;">TOTAL:</td>
                    <td style="text-align: right; padding: 4px 0;">{$symbol}{$total}</td>
                </tr>
            </table>
            
            <hr style="border: none; border-top: 1px dashed #333; margin: 12px 0;">
            
            <div style="text-align: center; font-size: 11px; color: #666;">
                <p>Thank you for your business!</p>
                <p>Please come again</p>
            </div>
        </div>
        HTML;
    }

    /**
     * Generate a branded invoice HTML
     */
    public function generateBrandedInvoiceHtml(Sale $sale): string
    {
        $businessName = Settings::businessConfiguration('business_name', config('app.name'));
        $businessAddress = Settings::businessConfiguration('business_address', '');
        $businessPhone = Settings::businessConfiguration('business_phone', '');
        $businessEmail = Settings::businessConfiguration('business_email', '');
        $businessWebsite = Settings::businessConfiguration('business_website', '');
        $taxId = Settings::businessConfiguration('tax_id', '');

        $currency = Settings::currencyLocalization('primary_currency', 'NGN');
        $symbol = $this->currencyService->getSymbol($currency);

        $items = $sale->saleItems()
            ->with('product')
            ->get()
            ->map(fn ($item) => [
                'name' => $item->product->name ?? 'Product',
                'description' => $item->product->description ?? '',
                'qty' => number_format($item->quantity, 2),
                'unit_price' => $this->currencyService->formatAmount($item->unit_price),
                'total' => $this->currencyService->formatAmount($item->total),
            ])
            ->toArray();

        $itemsHtml = '';
        foreach ($items as $item) {
            $itemsHtml .= "
            <tr style=\"border-bottom: 1px solid #eee;\">
                <td style=\"padding: 8px;\">{$item['name']}</td>
                <td style=\"text-align: center; padding: 8px;\">{$item['qty']}</td>
                <td style=\"text-align: right; padding: 8px;\">{$symbol}{$item['unit_price']}</td>
                <td style=\"text-align: right; padding: 8px;\">{$symbol}{$item['total']}</td>
            </tr>";
        }

        $subtotal = $this->currencyService->formatAmount($sale->subtotal);
        $discount = $this->currencyService->formatAmount($sale->discount);
        $tax = $this->currencyService->formatAmount($sale->tax);
        $total = $this->currencyService->formatAmount($sale->total);
        $date = $sale->created_at->format('Y-m-d');

        $discountRow = $sale->discount > 0 ? "
            <tr>
                <td colspan=\"2\" style=\"text-align: right; padding: 8px;\">Discount:</td>
                <td style=\"text-align: right; padding: 8px;\">-{$symbol}{$discount}</td>
            </tr>" : '';

        $taxRow = $sale->tax > 0 ? "
            <tr>
                <td colspan=\"2\" style=\"text-align: right; padding: 8px;\">Tax:</td>
                <td style=\"text-align: right; padding: 8px;\">{$symbol}{$tax}</td>
            </tr>" : '';

        return <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 40px; background: white;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 40px; border-bottom: 2px solid #333; padding-bottom: 20px;">
                <div>
                    <h1 style="margin: 0; font-size: 24px; color: #333;">{$businessName}</h1>
                    <p style="margin: 4px 0; color: #666;">{$businessAddress}</p>
                    <p style="margin: 4px 0; color: #666;">{$businessPhone} | {$businessEmail}</p>
                    {$businessWebsite}
                    <p style="margin: 4px 0; color: #666;">Tax ID: {$taxId}</p>
                </div>
                <div style="text-align: right;">
                    <h2 style="margin: 0; color: #333;">INVOICE</h2>
                    <p style="margin: 4px 0; color: #666;">Invoice #: {$sale->receipt_number}</p>
                    <p style="margin: 4px 0; color: #666;">Date: {$date}</p>
                </div>
            </div>
            
            <table style="width: 100%; margin-bottom: 30px;">
                <thead>
                    <tr style="background-color: #f5f5f5; border-top: 1px solid #333; border-bottom: 1px solid #333;">
                        <th style=\"text-align: left; padding: 12px; font-weight: bold;\">Item Description</th>
                        <th style=\"text-align: center; padding: 12px; font-weight: bold;\">Quantity</th>
                        <th style=\"text-align: right; padding: 12px; font-weight: bold;\">Unit Price</th>
                        <th style=\"text-align: right; padding: 12px; font-weight: bold;\">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    {$itemsHtml}
                </tbody>
            </table>
            
            <div style="display: flex; justify-content: flex-end; margin-bottom: 30px;">
                <table style="width: 400px;">
                    <tr style=\"border-bottom: 1px solid #ddd;\">
                        <td style=\"text-align: right; padding: 8px;\">Subtotal:</td>
                        <td style=\"text-align: right; padding: 8px; width: 120px;\">{$symbol}{$subtotal}</td>
                    </tr>
                    {$discountRow}
                    {$taxRow}
                    <tr style=\"background-color: #f5f5f5; border-top: 2px solid #333; border-bottom: 2px solid #333; font-weight: bold; font-size: 16px;\">
                        <td style=\"text-align: right; padding: 12px;\">TOTAL:</td>
                        <td style=\"text-align: right; padding: 12px;\">{$symbol}{$total}</td>
                    </tr>
                </table>
            </div>
            
            <div style="background-color: #f5f5f5; padding: 20px; text-align: center; color: #666; font-size: 12px;">
                <p>Thank you for your business!</p>
                <p>This is a computer-generated document. No signature required.</p>
            </div>
        </div>
        HTML;
    }

    /**
     * Get receipt data formatted for export
     */
    public function getReceiptExportData(Receipt $receipt): array
    {
        return [
            'receipt_number' => $receipt->receipt_number,
            'date' => $receipt->created_at->format('Y-m-d H:i:s'),
            'subtotal' => $this->currencyService->format($receipt->subtotal),
            'tax' => $this->currencyService->format($receipt->tax),
            'discount' => $this->currencyService->format($receipt->discount),
            'total' => $this->currencyService->format($receipt->total),
            'change_due' => $this->currencyService->format($receipt->change_due),
            'payments' => $receipt->payments,
        ];
    }
}

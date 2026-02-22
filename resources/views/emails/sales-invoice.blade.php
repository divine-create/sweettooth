<x-mail::message>
# Sales Invoice

Dear Customer,

Thank you for your purchase! Please find your invoice details below.

---

**Invoice Information**
- Invoice #: {{ $sale->receipt_number }}
- Date: {{ $sale->created_at->format('Y-m-d H:i:s') }}
- Order Type: {{ ucfirst(str_replace('-', ' ', $sale->order_type ?? 'dine-in')) }}

**Items Purchased**

| Item | Quantity | Unit Price | Total |
|------|----------|------------|-------|
@foreach($saleItems as $item)
| {{ $item['name'] }} | {{ $item['qty'] }} | {{ $symbol }} {{ $item['unit_price'] }} | {{ $symbol }} {{ $item['total'] }} |
@endforeach

---

**Amount Details**
- Subtotal: {{ $symbol }} {{ $currencyService->formatAmount($sale->subtotal) }}
@if($sale->discount > 0)
- Discount: -{{ $symbol }} {{ $currencyService->formatAmount($sale->discount) }}
@endif
@if($sale->tax > 0)
- Tax: {{ $symbol }} {{ $currencyService->formatAmount($sale->tax) }}
@endif
- **Total Amount: {{ $symbol }} {{ $currencyService->formatAmount($sale->total) }}**

**Status**
- Payment Status: {{ ucfirst(str_replace('_', ' ', $sale->payment_status ?? 'pending')) }}
- Fulfillment Status: {{ ucfirst(str_replace('_', ' ', $sale->fulfillment_status ?? 'pending')) }}

---

Thank you for your business!

Best regards,<br>
{{ \App\Helpers\Settings::businessConfiguration('business_name', config('app.name')) }}
</x-mail::message>

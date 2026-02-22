<x-mail::message>
# Sales Receipt

Dear {{ $receipt->customer_name ?? 'Customer' }},

Thank you for your purchase! Please find your receipt details below.

---

**Receipt Information**
- Receipt #: {{ $receipt->receipt_number }}
- Date: {{ $receipt->created_at->format('Y-m-d H:i:s') }}
- Sale ID: {{ $receipt->sale_id }}

**Items Purchased**

| Item | Quantity | Unit Price | Total |
|------|----------|------------|-------|
@foreach($receiptItems as $item)
| {{ $item['name'] }} | {{ $item['qty'] }} | {{ $symbol }} {{ $item['unit_price'] }} | {{ $symbol }} {{ $item['total'] }} |
@endforeach

---

**Order Summary**
- Subtotal: {{ $symbol }} {{ $currencyService->formatAmount($receipt->subtotal) }}
@if($receipt->discount > 0)
- Discount: {{ $symbol }} {{ $currencyService->formatAmount($receipt->discount) }}
@endif
@if($receipt->tax > 0)
- Tax: {{ $symbol }} {{ $currencyService->formatAmount($receipt->tax) }}
@endif
- **Total: {{ $symbol }} {{ $currencyService->formatAmount($receipt->total) }}**

**Payment Details**
@foreach($payments as $payment)
- {{ ucfirst($payment['method'] ?? 'Unknown') }}: {{ $symbol }} {{ $currencyService->formatAmount($payment['amount']) }}
@endforeach

@if($receipt->change_due > 0)
- Change Due: {{ $symbol }} {{ $currencyService->formatAmount($receipt->change_due) }}
@endif

---

Thank you for your business! We appreciate your patronage.

Best regards,<br>
{{ \App\Helpers\Settings::businessConfiguration('business_name', config('app.name')) }}
</x-mail::message>

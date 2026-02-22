@if($forCsv ?? false)
<table>
    <thead>
        <tr style="background-color: #E74C3C; color: white; font-weight: bold;">
            <th colspan="5" style="text-align: center; padding: 15px; font-size: 16pt;">Sales Analytics Report</th>
        </tr>
        <tr style="background-color: #c0392b; color: white;">
            <th colspan="3">Period: {{ \Carbon\Carbon::parse($data['period']['from'] ?? now())->format('d/m/Y H:i') }} to {{ \Carbon\Carbon::parse($data['period']['to'] ?? now())->format('d/m/Y H:i') }}</th>
            <th colspan="2">Generated: {{ now()->format('d/m/Y H:i:s') }}</th>
        </tr>
    </thead>
</table>

<!-- Overview Section -->
<table>
    <thead>
        <tr style="background-color: #27ae60; color: white; font-weight: bold;">
            <th colspan="4" style="padding: 10px;">SALES OVERVIEW</th>
        </tr>
        <tr style="background-color: #2C3E50; color: white;">
            <th>Metric</th>
            <th>Value</th>
            <th>Metric</th>
            <th>Value</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="font-weight: bold;">Total Sales</td>
            <td>{{ number_format($data['overview']['total_sales'] ?? 0, 2) }}</td>
            <td style="font-weight: bold;">Total Orders</td>
            <td>{{ $data['overview']['total_orders'] ?? 0 }}</td>
        </tr>
        <tr style="background-color: #f9f9f9;">
            <td style="font-weight: bold;">Average Order Value</td>
            <td>{{ number_format($data['overview']['avg_order_value'] ?? 0, 2) }}</td>
            <td style="font-weight: bold;">Net Revenue</td>
            <td>{{ number_format($data['overview']['net_revenue'] ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Total Discount</td>
            <td style="color: red;">{{ number_format($data['overview']['total_discount'] ?? 0, 2) }}</td>
            <td style="font-weight: bold;">Total Tax</td>
            <td>{{ number_format($data['overview']['total_tax'] ?? 0, 2) }}</td>
        </tr>
        <tr style="background-color: #f9f9f9;">
            <td style="font-weight: bold;">Total Refunds</td>
            <td style="color: red;">{{ number_format($data['overview']['total_refunds'] ?? 0, 2) }}</td>
            <td style="font-weight: bold;">Actual Revenue</td>
            <td style="color: green; font-weight: bold;">{{ number_format($data['overview']['actual_revenue'] ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Growth Rate</td>
            <td>{{ number_format($data['overview']['growth_rate'] ?? 0, 2) }}%</td>
            <td style="font-weight: bold;">Refund Rate</td>
            <td>{{ number_format($data['overview']['refund_rate'] ?? 0, 2) }}%</td>
        </tr>
    </tbody>
</table>

<table><tr><td></td></tr></table>

<!-- Top Products Section -->
@if(!empty($data['top_products']))
<table>
    <thead>
        <tr style="background-color: #3498db; color: white; font-weight: bold;">
            <th colspan="5" style="padding: 10px;">TOP SELLING PRODUCTS</th>
        </tr>
        <tr style="background-color: #2C3E50; color: white;">
            <th style="width: 5%;">#</th>
            <th style="width: 35%;">Product Name</th>
            <th style="width: 20%;">Quantity</th>
            <th style="width: 25%;">Revenue</th>
            <th style="width: 15%;">Orders</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data['top_products'] as $index => $product)
        <tr style="{{ $index % 2 == 0 ? '' : 'background-color: #f9f9f9;' }}">
            <td>{{ $index + 1 }}</td>
            <td style="font-weight: bold;">{{ $product['product']['name'] ?? 'N/A' }}</td>
            <td style="text-align: right;">{{ number_format($product['total_quantity'] ?? 0) }}</td>
            <td style="text-align: right;">{{ number_format($product['total_revenue'] ?? 0, 2) }}</td>
            <td style="text-align: right;">{{ number_format($product['order_count'] ?? 0) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
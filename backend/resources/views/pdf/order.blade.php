<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Order {{ $order->order_serial }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 24px;
            color: #2563eb;
            margin: 0 0 4px;
        }
        .header p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 24px;
        }
        .info-table td {
            vertical-align: top;
            padding: 4px 8px;
        }
        .info-table .label {
            font-weight: bold;
            color: #555;
            width: 120px;
        }
        .info-table .value {
            color: #333;
        }
        .customer-details {
            margin-bottom: 24px;
            padding: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        .customer-details h3 {
            margin: 0 0 8px;
            font-size: 14px;
            color: #2563eb;
        }
        .customer-details p {
            margin: 2px 0;
            color: #555;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items th {
            background: #2563eb;
            color: #fff;
            padding: 8px 6px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }
        table.items td {
            padding: 6px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
        }
        table.items .text-right {
            text-align: right;
        }
        table.items .text-center {
            text-align: center;
        }
        .totals {
            width: 300px;
            margin-left: auto;
        }
        .totals table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals td {
            padding: 4px 8px;
            font-size: 12px;
        }
        .totals .label {
            text-align: right;
            font-weight: bold;
            color: #555;
            width: 50%;
        }
        .totals .value {
            text-align: right;
            width: 50%;
        }
        .totals .grand-total td {
            font-size: 14px;
            font-weight: bold;
            border-top: 2px solid #2563eb;
            padding-top: 8px;
            color: #2563eb;
        }
        .notes {
            margin-top: 20px;
            padding: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        .notes h4 {
            margin: 0 0 6px;
            font-size: 12px;
            color: #555;
        }
        .notes p {
            margin: 0;
            font-size: 11px;
            color: #666;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>ORDER</h1>
        <p>{{ $order->order_serial }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Order Date:</td>
            <td class="value">{{ $order->order_date->format('d M Y') }}</td>
            <td class="label">Source:</td>
            <td class="value">{{ ucfirst($order->order_source) }}</td>
        </tr>
        <tr>
            <td class="label">Expected Delivery:</td>
            <td class="value">{{ $order->expected_delivery_date ? $order->expected_delivery_date->format('d M Y') : 'N/A' }}</td>
            <td class="label">Status:</td>
            <td class="value">{{ ucfirst($order->status) }}</td>
        </tr>
        <tr>
            <td class="label">Prepared By:</td>
            <td class="value">{{ $order->creator->first_name }} {{ $order->creator->last_name }}</td>
            <td class="label"></td>
            <td class="value"></td>
        </tr>
    </table>

    <div class="customer-details">
        <h3>Bill To</h3>
        <p><strong>{{ $order->customer->name }}</strong></p>
        @if ($order->customer->company_name)
            <p>{{ $order->customer->company_name }}</p>
        @endif
        <p>{{ $order->customer->billing_address }}</p>
        <p>{{ $order->customer->city }}, {{ $order->customer->state }} - {{ $order->customer->postal_code }}</p>
        @if ($order->customer->email)
            <p>Email: {{ $order->customer->email }}</p>
        @endif
        @if ($order->customer->phone)
            <p>Phone: {{ $order->customer->phone }}</p>
        @endif
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 40px;">#</th>
                <th>Description</th>
                <th style="width: 50px;" class="text-center">Qty</th>
                <th style="width: 50px;" class="text-center">Unit</th>
                <th style="width: 60px;" class="text-right">Unit Price</th>
                <th style="width: 40px;" class="text-right">Disc%</th>
                <th style="width: 50px;" class="text-right">Tax%</th>
                <th style="width: 70px;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->item_no }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="text-center">{{ number_format($item->ordered_quantity, 2) }}</td>
                    <td class="text-center">{{ $item->unit }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ $item->discount_percentage > 0 ? number_format($item->discount_percentage, 1) : '-' }}</td>
                    <td class="text-right">{{ $item->tax_percentage > 0 ? number_format($item->tax_percentage, 1) : '-' }}</td>
                    <td class="text-right">{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td class="label">Subtotal</td>
                <td class="value">{{ number_format($order->subtotal, 2) }}</td>
            </tr>
            @if ($order->discount_amount > 0)
                <tr>
                    <td class="label">Discount</td>
                    <td class="value">({{ number_format($order->discount_amount, 2) }})</td>
                </tr>
            @endif
            @if ($order->tax_amount > 0)
                <tr>
                    <td class="label">Tax</td>
                    <td class="value">{{ number_format($order->tax_amount, 2) }}</td>
                </tr>
            @endif
            <tr class="grand-total">
                <td class="label">Grand Total</td>
                <td class="value">{{ number_format($order->grand_total, 2) }}</td>
            </tr>
        </table>
    </div>

    @if ($order->reference_notes)
        <div class="notes">
            <h4>Reference Notes</h4>
            <p>{{ $order->reference_notes }}</p>
        </div>
    @endif

    @if ($order->notes)
        <div class="notes">
            <h4>Notes</h4>
            <p>{{ $order->notes }}</p>
        </div>
    @endif

    <div class="footer">
        This is a computer-generated order. Authorized signature required for confirmation.
    </div>

</body>
</html>

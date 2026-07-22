<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Delivery Challan {{ $deliveryChallan->serial }}</title>
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
        .transport-details {
            margin-bottom: 24px;
            padding: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        .transport-details h3 {
            margin: 0 0 8px;
            font-size: 14px;
            color: #2563eb;
        }
        .transport-details p {
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
        <h1>DELIVERY CHALLAN</h1>
        <p>{{ $deliveryChallan->serial }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Delivery Date:</td>
            <td class="value">{{ $deliveryChallan->delivery_date->format('d M Y') }}</td>
            <td class="label">Status:</td>
            <td class="value">{{ ucfirst($deliveryChallan->status) }}</td>
        </tr>
        <tr>
            <td class="label">Prepared By:</td>
            <td class="value">{{ $deliveryChallan->creator->first_name }} {{ $deliveryChallan->creator->last_name }}</td>
            <td class="label"></td>
            <td class="value"></td>
        </tr>
    </table>

    <div class="customer-details">
        <h3>Bill To</h3>
        <p><strong>{{ $deliveryChallan->customer->name }}</strong></p>
        @if ($deliveryChallan->customer->company_name)
            <p>{{ $deliveryChallan->customer->company_name }}</p>
        @endif
        <p>{{ $deliveryChallan->delivery_address }}</p>
        @if ($deliveryChallan->customer->email)
            <p>Email: {{ $deliveryChallan->customer->email }}</p>
        @endif
        @if ($deliveryChallan->customer->phone)
            <p>Phone: {{ $deliveryChallan->customer->phone }}</p>
        @endif
    </div>

    @if ($deliveryChallan->transport_name || $deliveryChallan->vehicle_number || $deliveryChallan->driver_name || $deliveryChallan->lr_number)
        <div class="transport-details">
            <h3>Transport Information</h3>
            @if ($deliveryChallan->transport_name)
                <p><strong>Transport:</strong> {{ $deliveryChallan->transport_name }}</p>
            @endif
            @if ($deliveryChallan->vehicle_number)
                <p><strong>Vehicle:</strong> {{ $deliveryChallan->vehicle_number }}</p>
            @endif
            @if ($deliveryChallan->driver_name)
                <p><strong>Driver:</strong> {{ $deliveryChallan->driver_name }}
                @if ($deliveryChallan->driver_mobile)
                    ({{ $deliveryChallan->driver_mobile }})
                @endif
                </p>
            @endif
            @if ($deliveryChallan->lr_number)
                <p><strong>LR No:</strong> {{ $deliveryChallan->lr_number }}</p>
            @endif
    </div>
@endif

@if ($deliveryChallan->delivery_by || $deliveryChallan->receiver_name)
    <div class="transport-details">
        <h3>Delivery Information</h3>
        @if ($deliveryChallan->delivery_by)
            <p><strong>Delivered By:</strong> {{ $deliveryChallan->delivery_by }}</p>
        @endif
        @if ($deliveryChallan->receiver_name)
            <p><strong>Received By:</strong> {{ $deliveryChallan->receiver_name }}</p>
        @endif
    </div>
@endif

<table class="items">
        <thead>
            <tr>
                <th style="width: 40px;">#</th>
                <th>Description</th>
                <th style="width: 50px;" class="text-center">Unit</th>
                <th style="width: 60px;" class="text-right">Order Qty</th>
                <th style="width: 60px;" class="text-right">Delivered Qty</th>
                <th style="width: 60px;" class="text-right">Rate</th>
                <th style="width: 70px;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($deliveryChallan->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->item_description }}</td>
                    <td class="text-center">{{ $item->unit }}</td>
                    <td class="text-right">{{ number_format($item->ordered_quantity, 2) }}</td>
                    <td class="text-right">{{ number_format($item->delivered_quantity, 2) }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->delivered_quantity * $item->unit_price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td class="label">Subtotal</td>
                <td class="value">{{ number_format($deliveryChallan->subtotal, 2) }}</td>
            </tr>
            @if ($deliveryChallan->discount_amount > 0)
                <tr>
                    <td class="label">Discount</td>
                    <td class="value">({{ number_format($deliveryChallan->discount_amount, 2) }})</td>
                </tr>
            @endif
            @if ($deliveryChallan->tax_amount > 0)
                <tr>
                    <td class="label">Tax</td>
                    <td class="value">{{ number_format($deliveryChallan->tax_amount, 2) }}</td>
                </tr>
            @endif
            <tr class="grand-total">
                <td class="label">Grand Total</td>
                <td class="value">{{ number_format($deliveryChallan->grand_total, 2) }}</td>
            </tr>
        </table>
    </div>

    @if ($deliveryChallan->remarks)
        <div class="notes">
            <h4>Remarks</h4>
            <p>{{ $deliveryChallan->remarks }}</p>
        </div>
    @endif

    <div class="footer">
        This is a computer-generated delivery challan. Authorized signature required for delivery.
    </div>

</body>
</html>

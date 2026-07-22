<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->serial }}</title>
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
        <h1>INVOICE</h1>
        <p>{{ $invoice->serial }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Invoice Date:</td>
            <td class="value">{{ $invoice->invoice_date->format('d M Y') }}</td>
            <td class="label">Due Date:</td>
            <td class="value">{{ $invoice->due_date->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="label">Status:</td>
            <td class="value">{{ ucfirst($invoice->status) }}</td>
            <td class="label">Prepared By:</td>
            <td class="value">{{ $invoice->creator->first_name }} {{ $invoice->creator->last_name }}</td>
        </tr>
    </table>

    <div class="customer-details">
        <h3>Bill To</h3>
        <p><strong>{{ $invoice->customer->name }}</strong></p>
        @if ($invoice->customer->company_name)
            <p>{{ $invoice->customer->company_name }}</p>
        @endif
        <p>{{ $invoice->billing_address }}</p>
        @if ($invoice->customer->email)
            <p>Email: {{ $invoice->customer->email }}</p>
        @endif
        @if ($invoice->customer->phone)
            <p>Phone: {{ $invoice->customer->phone }}</p>
        @endif
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 40px;">#</th>
                <th>Description</th>
                <th style="width: 50px;" class="text-center">Unit</th>
                <th style="width: 60px;" class="text-right">Qty</th>
                <th style="width: 60px;" class="text-right">Rate</th>
                <th style="width: 70px;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->item_description }}</td>
                    <td class="text-center">{{ $item->unit }}</td>
                    <td class="text-right">{{ number_format($item->invoiced_quantity, 2) }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td class="label">Subtotal</td>
                <td class="value">{{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            @if ($invoice->discount_amount > 0)
                <tr>
                    <td class="label">Discount</td>
                    <td class="value">({{ number_format($invoice->discount_amount, 2) }})</td>
                </tr>
            @endif
            @if ($invoice->tax_amount > 0)
                <tr>
                    <td class="label">Tax</td>
                    <td class="value">{{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
            @endif
            @if ($invoice->round_off != 0)
                <tr>
                    <td class="label">Round Off</td>
                    <td class="value">{{ number_format($invoice->round_off, 2) }}</td>
                </tr>
            @endif
            <tr class="grand-total">
                <td class="label">Grand Total</td>
                <td class="value">{{ number_format($invoice->grand_total, 2) }}</td>
            </tr>
        </table>
    </div>

    @if ($invoice->remarks)
        <div class="notes">
            <h4>Remarks</h4>
            <p>{{ $invoice->remarks }}</p>
        </div>
    @endif

    <div class="footer">
        This is a computer-generated invoice.
    </div>

</body>
</html>

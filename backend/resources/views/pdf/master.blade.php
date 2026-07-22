<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $documentTitle }} {{ $document->quotation_serial ?? $document->order_serial ?? $document->sales_order_serial ?? $document->serial }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .info-table {
            width: 100%;
            margin: 16px 0 20px;
        }
        .info-table td {
            vertical-align: top;
            padding: 3px 8px;
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
            margin-bottom: 20px;
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
        .transport-details {
            margin-bottom: 20px;
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
    </style>
</head>
<body>

    @include('pdf.partials.header')

    @yield('content')

    @include('pdf.partials.footer')

</body>
</html>

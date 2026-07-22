@extends('pdf.master')

@section('content')
    <table class="info-table">
        <tr>
            <td class="label">Receipt Number:</td>
            <td class="value">{{ $document->payment_no }}</td>
            <td class="label">Date:</td>
            <td class="value">{{ $document->payment_date->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="label">Payment Method:</td>
            <td class="value">{{ $document->payment_method }}</td>
            <td class="label">Reference No:</td>
            <td class="value">{{ $document->reference_no ?? '-' }}</td>
        </tr>
    </table>

    <div class="customer-details">
        <h3>Received From</h3>
        <p><strong>{{ $document->customer->name }}</strong></p>
        @if ($document->customer->company_name)
            <p>{{ $document->customer->company_name }}</p>
        @endif
        <p>{{ $document->customer->billing_address }}</p>
        <p>{{ $document->customer->city }}, {{ $document->customer->state }} - {{ $document->customer->postal_code }}</p>
        @if ($document->customer->email)
            <p>Email: {{ $document->customer->email }}</p>
        @endif
        @if ($document->customer->phone)
            <p>Phone: {{ $document->customer->phone }}</p>
        @endif
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 40px;">#</th>
                <th>Invoice No</th>
                <th style="width: 80px;" class="text-right">Invoice Amount</th>
                <th style="width: 80px;" class="text-right">Paid Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($document->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->invoice->serial }}</td>
                    <td class="text-right">{{ number_format($item->invoice->grand_total, 2) }}</td>
                    <td class="text-right">{{ number_format($item->paid_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr class="grand-total">
                <td class="label">Total Received</td>
                <td class="value">{{ number_format($document->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    @if ($document->remarks)
        <div class="notes">
            <h4>Remarks</h4>
            <p>{{ $document->remarks }}</p>
        </div>
    @endif
@endsection

<?php

namespace App\Services;

use App\Models\DeliveryChallan;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\SalesOrder;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfEngineService
{
    private const VIEWS = [
        'quotation' => 'pdf.quotation',
        'order' => 'pdf.order',
        'sales-order' => 'pdf.sales-order',
        'delivery-challan' => 'pdf.delivery-challan',
        'invoice' => 'pdf.invoice',
        'payment' => 'pdf.payment',
    ];

    private const TITLES = [
        'quotation' => 'QUOTATION',
        'order' => 'ORDER BOOKING',
        'sales-order' => 'SALES ORDER',
        'delivery-challan' => 'DELIVERY CHALLAN',
        'invoice' => 'TAX INVOICE',
        'payment' => 'PAYMENT RECEIPT',
    ];

    private const RELATIONS = [
        'quotation' => ['customer', 'creator', 'items'],
        'order' => ['customer', 'creator', 'items'],
        'sales-order' => ['customer', 'creator', 'items'],
        'delivery-challan' => ['customer', 'creator', 'items'],
        'invoice' => ['customer', 'creator', 'items'],
        'payment' => ['customer', 'creator', 'items.invoice'],
    ];

    public function generate(Quotation|Order|SalesOrder|DeliveryChallan|Invoice|Payment $document): \Barryvdh\DomPDF\PDF
    {
        $type = $this->resolveType($document);

        $document->load($this->getRelations($type));

        $pdf = Pdf::loadView($this->getView($type), [
            'document' => $document,
            'documentTitle' => $this->getTitle($type),
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    public function getFilename(Quotation|Order|SalesOrder|DeliveryChallan|Invoice|Payment $document): string
    {
        return str_replace('/', '_', $this->getSerial($document)) . '.pdf';
    }

    private function resolveType(Quotation|Order|SalesOrder|DeliveryChallan|Invoice|Payment $document): string
    {
        return match (true) {
            $document instanceof Quotation => 'quotation',
            $document instanceof Order => 'order',
            $document instanceof SalesOrder => 'sales-order',
            $document instanceof DeliveryChallan => 'delivery-challan',
            $document instanceof Invoice => 'invoice',
            $document instanceof Payment => 'payment',
        };
    }

    private function getView(string $type): string
    {
        return self::VIEWS[$type];
    }

    private function getTitle(string $type): string
    {
        return self::TITLES[$type];
    }

    private function getRelations(string $type): array
    {
        return self::RELATIONS[$type];
    }

    private function getSerial(Quotation|Order|SalesOrder|DeliveryChallan|Invoice|Payment $document): string
    {
        return match (true) {
            $document instanceof Quotation => $document->quotation_serial,
            $document instanceof Order => $document->order_serial,
            $document instanceof SalesOrder => $document->sales_order_serial,
            $document instanceof DeliveryChallan => $document->serial,
            $document instanceof Invoice => $document->serial,
            $document instanceof Payment => $document->payment_no,
        };
    }
}

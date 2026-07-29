<?php

namespace App\Services;

use App\Models\DeliveryChallan;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfEngineService
{
    private const VIEWS = [
        'order' => 'pdf.order',
        'delivery-challan' => 'pdf.delivery-challan',
        'invoice' => 'pdf.invoice',
        'payment' => 'pdf.payment',
    ];

    private const TITLES = [
        'order' => 'ORDER BOOKING',
        'delivery-challan' => 'DELIVERY CHALLAN',
        'invoice' => 'TAX INVOICE',
        'payment' => 'PAYMENT RECEIPT',
    ];

    private const RELATIONS = [
        'order' => ['customer', 'creator', 'items'],
        'delivery-challan' => ['customer', 'creator', 'items'],
        'invoice' => ['customer', 'creator', 'items'],
        'payment' => ['customer', 'creator', 'items.invoice'],
    ];

    public function generate(Order|DeliveryChallan|Invoice|Payment $document): \Barryvdh\DomPDF\PDF
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

    public function getFilename(Order|DeliveryChallan|Invoice|Payment $document): string
    {
        return str_replace('/', '_', $this->getSerial($document)) . '.pdf';
    }

    private function resolveType(Order|DeliveryChallan|Invoice|Payment $document): string
    {
        return match (true) {
            $document instanceof Order => 'order',
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

    private function getSerial(Order|DeliveryChallan|Invoice|Payment $document): string
    {
        return match (true) {
            $document instanceof Order => $document->order_serial,
            $document instanceof DeliveryChallan => $document->serial,
            $document instanceof Invoice => $document->serial,
            $document instanceof Payment => $document->payment_no,
        };
    }
}

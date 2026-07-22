<?php

namespace App\Services;

use App\Models\DeliveryChallan;
use Barryvdh\DomPDF\Facade\Pdf;

class DeliveryChallanPDFService
{
    public function generateDC(DeliveryChallan $deliveryChallan): \Barryvdh\DomPDF\PDF
    {
        $deliveryChallan->load(['customer', 'creator', 'items']);

        $pdf = Pdf::loadView('pdf.delivery-challan', [
            'deliveryChallan' => $deliveryChallan,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    public function getFilename(DeliveryChallan $deliveryChallan): string
    {
        return str_replace('/', '_', $deliveryChallan->serial) . '.pdf';
    }
}

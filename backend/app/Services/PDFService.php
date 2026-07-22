<?php

namespace App\Services;

use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;

class PDFService
{
    public function generateQuotationPDF(Quotation $quotation): \Barryvdh\DomPDF\PDF
    {
        $quotation->load(['customer', 'creator', 'items']);

        $pdf = Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    public function getFilename(Quotation $quotation): string
    {
        return str_replace('/', '_', $quotation->quotation_serial) . '.pdf';
    }
}

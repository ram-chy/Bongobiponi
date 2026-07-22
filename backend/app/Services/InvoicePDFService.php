<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePDFService
{
    public function generateInvoice(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $invoice->load(['customer', 'creator', 'items']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    public function getFilename(Invoice $invoice): string
    {
        return str_replace('/', '_', $invoice->serial) . '.pdf';
    }
}

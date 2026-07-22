<?php

namespace App\Services;

use App\Models\SalesOrder;
use Barryvdh\DomPDF\Facade\Pdf;

class SalesOrderPDFService
{
    public function generateSalesOrderPDF(SalesOrder $salesOrder): \Barryvdh\DomPDF\PDF
    {
        $salesOrder->load(['customer', 'creator', 'items']);

        $pdf = Pdf::loadView('pdf.sales-order', [
            'salesOrder' => $salesOrder,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    public function getFilename(SalesOrder $salesOrder): string
    {
        return str_replace('/', '_', $salesOrder->sales_order_serial) . '.pdf';
    }
}

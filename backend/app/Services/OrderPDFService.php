<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderPDFService
{
    public function generateOrderPDF(Order $order): \Barryvdh\DomPDF\PDF
    {
        $order->load(['customer', 'creator', 'items']);

        $pdf = Pdf::loadView('pdf.order', [
            'order' => $order,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    public function getFilename(Order $order): string
    {
        return str_replace('/', '_', $order->order_serial) . '.pdf';
    }
}

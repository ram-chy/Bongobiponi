<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceCollection;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\ActivityLogService;
use App\Services\InvoicePDFService;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly InvoicePDFService $pdfService,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function index(Request $request): InvoiceCollection
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = $this->invoiceService->list($request->only([
            'search', 'status', 'customer_id',
            'date_from', 'date_to', 'sort', 'direction', 'per_page',
        ]));

        return new InvoiceCollection($invoices);
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $this->authorize('create', Invoice::class);

        $invoice = $this->invoiceService->store([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return $this->successResponse(new InvoiceResource($invoice), 'Invoice created successfully', 201);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        $invoice->load([
            'customer',
            'creator',
            'updater',
            'items.deliveryChallan',
            'items.deliveryChallanItem',
            'items.salesOrder',
            'items.salesOrderItem',
            'items.orderBooking',
            'items.quotation',
        ]);

        return $this->successResponse(new InvoiceResource($invoice), 'Invoice retrieved successfully');
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        $invoice = $this->invoiceService->update($invoice, $request->validated());

        return $this->successResponse(new InvoiceResource($invoice), 'Invoice updated successfully');
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $this->authorize('delete', $invoice);

        $this->invoiceService->delete($invoice);

        return $this->successResponse(null, 'Invoice deleted successfully');
    }

    public function restore(int $id): JsonResponse
    {
        $invoice = $this->invoiceService->findTrashed($id);

        $this->authorize('restore', $invoice);

        $invoice = $this->invoiceService->restore($invoice);

        return $this->successResponse(new InvoiceResource($invoice), 'Invoice restored successfully');
    }

    public function invoiceableItems(int $deliveryChallanId): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $items = $this->invoiceService->getInvoiceableItems($deliveryChallanId);

        return $this->successResponse($items);
    }

    public function downloadPDF(Invoice $invoice): \Illuminate\Http\Response
    {
        $this->authorize('view', $invoice);

        $pdf = $this->pdfService->generateInvoice($invoice);
        $filename = $this->pdfService->getFilename($invoice);

        return $pdf->download($filename);
    }
}

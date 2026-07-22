<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Resources\PaymentCollection;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\ActivityLogService;
use App\Services\PaymentService;
use App\Services\PdfEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly PdfEngineService $pdfEngineService,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function index(Request $request): PaymentCollection
    {
        $this->authorize('viewAny', Payment::class);

        $payments = $this->paymentService->list($request->only([
            'search', 'payment_method', 'customer_id',
            'date_from', 'date_to', 'sort', 'direction', 'per_page',
        ]));

        return new PaymentCollection($payments);
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $this->authorize('create', Payment::class);

        $payment = $this->paymentService->store([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return $this->successResponse(new PaymentResource($payment), 'Payment created successfully', 201);
    }

    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        $payment->load([
            'customer',
            'creator',
            'updater',
            'items.invoice',
        ]);

        return $this->successResponse(new PaymentResource($payment), 'Payment retrieved successfully');
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): JsonResponse
    {
        $this->authorize('update', $payment);

        $payment = $this->paymentService->update($payment, $request->validated());

        return $this->successResponse(new PaymentResource($payment), 'Payment updated successfully');
    }

    public function destroy(Payment $payment): JsonResponse
    {
        $this->authorize('delete', $payment);

        $this->paymentService->delete($payment);

        $this->activityLogService->logDelete('payment', 'payment', $payment->id);

        return $this->successResponse(null, 'Payment deleted successfully');
    }

    public function restore(int $id): JsonResponse
    {
        $payment = $this->paymentService->findTrashed($id);

        $this->authorize('restore', $payment);

        $payment = $this->paymentService->restore($payment);

        return $this->successResponse(new PaymentResource($payment), 'Payment restored successfully');
    }

    public function downloadPDF(Payment $payment): \Illuminate\Http\Response
    {
        $this->authorize('view', $payment);

        $pdf = $this->pdfEngineService->generate($payment);
        $filename = $this->pdfEngineService->getFilename($payment);

        return $pdf->download($filename);
    }

    public function dueInvoices(int $customerId): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $invoices = $this->paymentService->getDueInvoices($customerId);

        return $this->successResponse($invoices);
    }
}

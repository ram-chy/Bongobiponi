<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuotationRequest;
use App\Http\Requests\UpdateQuotationRequest;
use App\Http\Resources\QuotationCollection;
use App\Http\Resources\QuotationResource;
use App\Models\Quotation;
use App\Services\PDFService;
use App\Services\QuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    public function __construct(
        private readonly QuotationService $quotationService,
        private readonly PDFService $pdfService,
    ) {}

    public function index(Request $request): QuotationCollection
    {
        $this->authorize('viewAny', Quotation::class);

        $quotations = $this->quotationService->list($request->only([
            'search', 'status', 'customer_id', 'date_from', 'date_to', 'sort', 'direction', 'per_page',
        ]));

        return new QuotationCollection($quotations);
    }

    public function store(StoreQuotationRequest $request): JsonResponse
    {
        $this->authorize('create', Quotation::class);

        $quotation = $this->quotationService->store([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return $this->successResponse(new QuotationResource($quotation), 'Quotation created successfully', 201);
    }

    public function show(Quotation $quotation): JsonResponse
    {
        $this->authorize('view', $quotation);

        $quotation->load(['customer', 'creator', 'items']);

        return $this->successResponse(new QuotationResource($quotation), 'Quotation retrieved successfully');
    }

    public function update(UpdateQuotationRequest $request, Quotation $quotation): JsonResponse
    {
        $this->authorize('update', $quotation);

        $quotation = $this->quotationService->update($quotation, $request->validated());

        return $this->successResponse(new QuotationResource($quotation), 'Quotation updated successfully');
    }

    public function destroy(Quotation $quotation): JsonResponse
    {
        $this->authorize('delete', $quotation);

        $quotation->delete();

        return $this->successResponse(null, 'Quotation deleted successfully');
    }

    public function restore(int $id): JsonResponse
    {
        $quotation = $this->quotationService->findTrashed($id);

        $this->authorize('restore', $quotation);

        $quotation = $this->quotationService->restore($quotation);

        return $this->successResponse(new QuotationResource($quotation), 'Quotation restored successfully');
    }

    public function downloadPDF(Quotation $quotation): \Illuminate\Http\Response
    {
        $this->authorize('view', $quotation);

        $pdf = $this->pdfService->generateQuotationPDF($quotation);
        $filename = $this->pdfService->getFilename($quotation);

        return $pdf->download($filename);
    }
}

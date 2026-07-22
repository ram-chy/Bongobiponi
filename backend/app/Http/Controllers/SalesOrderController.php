<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalesOrderRequest;
use App\Http\Requests\UpdateSalesOrderRequest;
use App\Http\Resources\SalesOrderCollection;
use App\Http\Resources\SalesOrderResource;
use App\Models\SalesOrder;
use App\Services\ActivityLogService;
use App\Services\SalesOrderPDFService;
use App\Services\SalesOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesOrderController extends Controller
{
    public function __construct(
        private readonly SalesOrderService $salesOrderService,
        private readonly SalesOrderPDFService $pdfService,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function index(Request $request): SalesOrderCollection
    {
        $this->authorize('viewAny', SalesOrder::class);

        $salesOrders = $this->salesOrderService->list($request->only([
            'search', 'status', 'customer_id', 'created_by',
            'sales_order_serial', 'date_from', 'date_to', 'sort', 'direction', 'per_page',
        ]));

        return new SalesOrderCollection($salesOrders);
    }

    public function store(StoreSalesOrderRequest $request): JsonResponse
    {
        $this->authorize('create', SalesOrder::class);

        $salesOrder = $this->salesOrderService->store([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return $this->successResponse(new SalesOrderResource($salesOrder), 'Sales Order created successfully', 201);
    }

    public function show(SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('view', $salesOrder);

        $salesOrder->load(['customer', 'creator', 'approver', 'items']);

        return $this->successResponse(new SalesOrderResource($salesOrder), 'Sales Order retrieved successfully');
    }

    public function update(UpdateSalesOrderRequest $request, SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('update', $salesOrder);

        $salesOrder = $this->salesOrderService->update($salesOrder, $request->validated());

        return $this->successResponse(new SalesOrderResource($salesOrder), 'Sales Order updated successfully');
    }

    public function destroy(SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('delete', $salesOrder);

        $salesOrder->delete();

        $this->activityLogService->logDelete('sales_order', 'sales_order', $salesOrder->id);

        return $this->successResponse(null, 'Sales Order deleted successfully');
    }

    public function restore(int $id): JsonResponse
    {
        $salesOrder = $this->salesOrderService->findTrashed($id);

        $this->authorize('restore', $salesOrder);

        $salesOrder = $this->salesOrderService->restore($salesOrder);

        return $this->successResponse(new SalesOrderResource($salesOrder), 'Sales Order restored successfully');
    }

    public function downloadPDF(SalesOrder $salesOrder): \Illuminate\Http\Response
    {
        $this->authorize('view', $salesOrder);

        $pdf = $this->pdfService->generateSalesOrderPDF($salesOrder);
        $filename = $this->pdfService->getFilename($salesOrder);

        return $pdf->download($filename);
    }
}

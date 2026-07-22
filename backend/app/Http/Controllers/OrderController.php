<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderPDFService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderPDFService $pdfService,
    ) {}

    public function index(Request $request): OrderCollection
    {
        $this->authorize('viewAny', Order::class);

        $orders = $this->orderService->list($request->only([
            'search', 'status', 'customer_id', 'order_source', 'created_by',
            'order_serial', 'date_from', 'date_to', 'sort', 'direction', 'per_page',
        ]));

        return new OrderCollection($orders);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $this->authorize('create', Order::class);

        $order = $this->orderService->store([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return $this->successResponse(new OrderResource($order), 'Order created successfully', 201);
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->load(['customer', 'creator', 'approver', 'items']);

        return $this->successResponse(new OrderResource($order), 'Order retrieved successfully');
    }

    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        $order = $this->orderService->update($order, $request->validated());

        return $this->successResponse(new OrderResource($order), 'Order updated successfully');
    }

    public function destroy(Order $order): JsonResponse
    {
        $this->authorize('delete', $order);

        $order->delete();

        return $this->successResponse(null, 'Order deleted successfully');
    }

    public function restore(int $id): JsonResponse
    {
        $order = $this->orderService->findTrashed($id);

        $this->authorize('restore', $order);

        $order = $this->orderService->restore($order);

        return $this->successResponse(new OrderResource($order), 'Order restored successfully');
    }

    public function downloadPDF(Order $order): \Illuminate\Http\Response
    {
        $this->authorize('view', $order);

        $pdf = $this->pdfService->generateOrderPDF($order);
        $filename = $this->pdfService->getFilename($order);

        return $pdf->download($filename);
    }
}

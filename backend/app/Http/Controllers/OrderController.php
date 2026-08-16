<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderCommentRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderCommentResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderStatusHistoryResource;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderAvailabilityService;
use App\Services\OrderPDFService;
use App\Services\OrderService;
use App\Services\OrderStatusTransitionService;
use App\Services\PurchaseOrderSynchronizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderPDFService $pdfService,
        private readonly OrderStatusTransitionService $statusTransitionService,
        private readonly OrderAvailabilityService $availabilityService,
        private readonly \App\Services\OrderStockReservationService $reservationService,
        private readonly PurchaseOrderSynchronizationService $purchaseSyncService,
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

    public function transitionStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        $targetStatus = $request->enum('status', OrderStatus::class);

        try {
            $order = $this->statusTransitionService->transition($order, $targetStatus);
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        if ($targetStatus === OrderStatus::Cancelled) {
            $bookIds = $order->items()->pluck('book_id')->filter()->unique()->values()->all();

            // Runs in a separate transaction after cancellation commits so a
            // re-allocation failure can never undo a valid cancellation.
            try {
                $this->purchaseSyncService->syncAfterRelease($bookIds);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning(
                    "Order re-allocation after cancellation failed for order #{$order->id}: {$e->getMessage()}"
                );
            }

            return $this->successResponse(
                new OrderResource($order->load(['customer', 'creator', 'items'])),
                'Order cancelled successfully',
            );
        }

        return $this->successResponse(
            new OrderResource($order->load(['customer', 'creator', 'items'])),
            'Order status updated successfully',
        );
    }

    public function availability(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return $this->successResponse(
            $this->availabilityService->check($order),
            'Order availability retrieved successfully',
        );
    }

    public function statusHistory(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $history = $order->statusHistories()
            ->with('changedBy')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return $this->successResponse(
            OrderStatusHistoryResource::collection($history)->resolve(),
            'Order status history retrieved successfully',
        );
    }

    public function reservations(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $reservations = $this->reservationService->getReservationsForOrder($order);

        return $this->successResponse(
            $reservations,
            'Order reservations retrieved successfully',
        );
    }

    public function comments(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $comments = $order->comments()
            ->with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return $this->successResponse(
            OrderCommentResource::collection($comments)->resolve(),
            'Order comments retrieved successfully',
        );
    }

    public function storeComment(StoreOrderCommentRequest $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $comment = $order->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $request->validated('comment'),
        ]);

        return $this->successResponse(
            new OrderCommentResource($comment->load('user')),
            'Comment added successfully',
            201,
        );
    }
}

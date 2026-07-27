<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReceiveOrderRequest;
use App\Http\Requests\UpdateReceiveOrderRequest;
use App\Http\Resources\ReceiveOrderCollection;
use App\Http\Resources\ReceiveOrderResource;
use App\Models\ReceiveOrder;
use App\Services\ReceiveOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceiveOrderController extends Controller
{
    public function __construct(
        private readonly ReceiveOrderService $service,
    ) {}

    public function index(Request $request): ReceiveOrderCollection
    {
        $this->authorize('viewAny', ReceiveOrder::class);

        $receiveOrders = $this->service->list($request->only([
            'search', 'status', 'supplier_id', 'date_from', 'date_to',
            'sort', 'direction', 'per_page',
        ]));

        return new ReceiveOrderCollection($receiveOrders);
    }

    public function store(StoreReceiveOrderRequest $request): JsonResponse
    {
        $this->authorize('create', ReceiveOrder::class);

        $receiveOrder = $this->service->store([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return $this->successResponse(
            new ReceiveOrderResource($receiveOrder->load(['supplier', 'customer', 'creator', 'items.book'])),
            'Receive order created successfully.',
            201,
        );
    }

    public function show(ReceiveOrder $receiveOrder): ReceiveOrderResource
    {
        $this->authorize('view', $receiveOrder);

        return new ReceiveOrderResource($receiveOrder->load(['supplier', 'customer', 'creator', 'updater', 'items.book']));
    }

    public function update(UpdateReceiveOrderRequest $request, ReceiveOrder $receiveOrder): JsonResponse
    {
        $this->authorize('update', $receiveOrder);

        $receiveOrder = $this->service->update($receiveOrder, $request->validated());

        return $this->successResponse(
            new ReceiveOrderResource($receiveOrder),
            'Receive order updated successfully.',
        );
    }

    public function destroy(ReceiveOrder $receiveOrder): JsonResponse
    {
        $this->authorize('delete', $receiveOrder);

        $this->service->delete($receiveOrder);

        return $this->successResponse(null, 'Receive order deleted successfully.');
    }

    public function restore(int $id): JsonResponse
    {
        $receiveOrder = $this->service->findTrashed($id);

        $this->authorize('restore', $receiveOrder);

        $receiveOrder = $this->service->restore($receiveOrder);

        return $this->successResponse(
            new ReceiveOrderResource($receiveOrder),
            'Receive order restored successfully.',
        );
    }

    public function approve(ReceiveOrder $receiveOrder): JsonResponse
    {
        $this->authorize('update', $receiveOrder);

        $receiveOrder = $this->service->approve($receiveOrder);

        return $this->successResponse(
            new ReceiveOrderResource($receiveOrder),
            'Receive order approved successfully.',
        );
    }

    public function receive(Request $request, ReceiveOrder $receiveOrder): JsonResponse
    {
        $this->authorize('update', $receiveOrder);

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:receive_order_items,id',
            'items.*.received_quantity' => 'required|integer|min:1',
        ]);

        $receiveOrder = $this->service->receive($receiveOrder, $request->input('items'));

        return $this->successResponse(
            new ReceiveOrderResource($receiveOrder),
            'Items received successfully.',
        );
    }

    public function cancel(ReceiveOrder $receiveOrder): JsonResponse
    {
        $this->authorize('update', $receiveOrder);

        $receiveOrder = $this->service->cancel($receiveOrder);

        return $this->successResponse(
            new ReceiveOrderResource($receiveOrder),
            'Receive order cancelled successfully.',
        );
    }
}

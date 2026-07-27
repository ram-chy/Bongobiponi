<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Http\Resources\PurchaseCollection;
use App\Http\Resources\PurchaseResource;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseService $service,
    ) {}

    public function index(Request $request): PurchaseCollection
    {
        $this->authorize('viewAny', Purchase::class);

        $purchases = $this->service->list($request->only([
            'search', 'status', 'supplier_id', 'purchase_type',
            'date_from', 'date_to', 'sort', 'direction', 'per_page',
        ]));

        return new PurchaseCollection($purchases);
    }

    public function store(StorePurchaseRequest $request): JsonResponse
    {
        $this->authorize('create', Purchase::class);

        $purchase = $this->service->store([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return $this->successResponse(
            new PurchaseResource($purchase->load(['supplier', 'creator', 'items.book'])),
            'Purchase created successfully.',
            201,
        );
    }

    public function show(Purchase $purchase): PurchaseResource
    {
        $this->authorize('view', $purchase);

        return new PurchaseResource($purchase->load(['supplier', 'receiveOrder', 'creator', 'updater', 'items.book']));
    }

    public function update(UpdatePurchaseRequest $request, Purchase $purchase): JsonResponse
    {
        $this->authorize('update', $purchase);

        $purchase = $this->service->update($purchase, $request->validated());

        return $this->successResponse(
            new PurchaseResource($purchase),
            'Purchase updated successfully.',
        );
    }

    public function destroy(Purchase $purchase): JsonResponse
    {
        $this->authorize('delete', $purchase);

        $this->service->delete($purchase);

        return $this->successResponse(null, 'Purchase deleted successfully.');
    }

    public function confirm(Purchase $purchase): JsonResponse
    {
        $this->authorize('update', $purchase);

        $purchase = $this->service->confirm($purchase);

        return $this->successResponse(
            new PurchaseResource($purchase),
            'Purchase confirmed successfully.',
        );
    }

    public function cancel(Purchase $purchase): JsonResponse
    {
        $this->authorize('update', $purchase);

        $purchase = $this->service->cancel($purchase);

        return $this->successResponse(
            new PurchaseResource($purchase),
            'Purchase cancelled successfully.',
        );
    }

    public function restore(int $id): JsonResponse
    {
        $purchase = $this->service->findTrashed($id);

        $this->authorize('restore', $purchase);

        $purchase = $this->service->restore($purchase);

        return $this->successResponse(
            new PurchaseResource($purchase),
            'Purchase restored successfully.',
        );
    }
}

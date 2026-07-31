<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeliveryChallanRequest;
use App\Http\Requests\UpdateDeliveryChallanRequest;
use App\Http\Resources\DeliveryChallanCollection;
use App\Http\Resources\DeliveryChallanResource;
use App\Models\DeliveryChallan;
use App\Services\ActivityLogService;
use App\Services\DeliveryChallanPDFService;
use App\Services\DeliveryChallanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryChallanController extends Controller
{
    public function __construct(
        private readonly DeliveryChallanService $deliveryChallanService,
        private readonly DeliveryChallanPDFService $pdfService,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function index(Request $request): DeliveryChallanCollection
    {
        $this->authorize('viewAny', DeliveryChallan::class);

        $deliveryChallans = $this->deliveryChallanService->list($request->only([
            'search', 'status', 'customer_id',
            'date_from', 'date_to', 'sort', 'direction', 'per_page',
        ]));

        return new DeliveryChallanCollection($deliveryChallans);
    }

    public function store(StoreDeliveryChallanRequest $request): JsonResponse
    {
        $this->authorize('create', DeliveryChallan::class);

        $deliveryChallan = $this->deliveryChallanService->store([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return $this->successResponse(new DeliveryChallanResource($deliveryChallan), 'Delivery Challan created successfully', 201);
    }

    public function show(DeliveryChallan $deliveryChallan): JsonResponse
    {
        $this->authorize('view', $deliveryChallan);

        $deliveryChallan->load([
            'customer',
            'creator',
            'updater',
            'items.orderBooking',
        ]);

        return $this->successResponse(new DeliveryChallanResource($deliveryChallan), 'Delivery Challan retrieved successfully');
    }

    public function update(UpdateDeliveryChallanRequest $request, DeliveryChallan $deliveryChallan): JsonResponse
    {
        $this->authorize('update', $deliveryChallan);

        $deliveryChallan = $this->deliveryChallanService->update($deliveryChallan, $request->validated());

        return $this->successResponse(new DeliveryChallanResource($deliveryChallan), 'Delivery Challan updated successfully');
    }

    public function destroy(DeliveryChallan $deliveryChallan): JsonResponse
    {
        $this->authorize('delete', $deliveryChallan);

        $this->deliveryChallanService->delete($deliveryChallan);

        return $this->successResponse(null, 'Delivery Challan deleted successfully');
    }

    public function restore(int $id): JsonResponse
    {
        $deliveryChallan = $this->deliveryChallanService->findTrashed($id);

        $this->authorize('restore', $deliveryChallan);

        $deliveryChallan = $this->deliveryChallanService->restore($deliveryChallan);

        return $this->successResponse(new DeliveryChallanResource($deliveryChallan), 'Delivery Challan restored successfully');
    }

    public function remainingItems(int $orderId): JsonResponse
    {
        $this->authorize('viewAny', DeliveryChallan::class);

        $order = $this->deliveryChallanService->getRemainingOrderItems($orderId);

        return $this->successResponse($order);
    }

    public function downloadPDF(DeliveryChallan $deliveryChallan): \Illuminate\Http\Response
    {
        $this->authorize('view', $deliveryChallan);

        $pdf = $this->pdfService->generateDC($deliveryChallan);
        $filename = $this->pdfService->getFilename($deliveryChallan);

        return $pdf->download($filename);
    }
}

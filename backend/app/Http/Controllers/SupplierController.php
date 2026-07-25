<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierCollection;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierService $supplierService,
    ) {}

    public function index(Request $request): SupplierCollection
    {
        $this->authorize('viewAny', Supplier::class);

        $suppliers = $this->supplierService->list($request->only([
            'search', 'status', 'sort', 'direction', 'per_page',
        ]));

        return new SupplierCollection($suppliers);
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $this->authorize('create', Supplier::class);

        $supplier = $this->supplierService->store([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return $this->successResponse(new SupplierResource($supplier), 'Supplier created successfully', 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        $supplier->load(['creator', 'updater']);

        return $this->successResponse(new SupplierResource($supplier), 'Supplier retrieved successfully');
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);

        $supplier = $this->supplierService->update($supplier, $request->validated());

        return $this->successResponse(new SupplierResource($supplier), 'Supplier updated successfully');
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $this->authorize('delete', $supplier);

        $supplier->delete();

        return $this->successResponse(null, 'Supplier deleted successfully');
    }

    public function restore(int $id): JsonResponse
    {
        // CRIT-018 fix: Authorize BEFORE restoring
        $supplier = Supplier::withTrashed()->findOrFail($id);
        $this->authorize('restore', $supplier);
        $this->supplierService->restore($id);

        return $this->successResponse(new SupplierResource($supplier->fresh()->load(['creator', 'updater'])), 'Supplier restored successfully');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerCollection;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {}

    public function index(Request $request): CustomerCollection
    {
        $this->authorize('viewAny', Customer::class);

        $customers = $this->customerService->list($request->only([
            'search', 'status', 'city', 'state', 'sort', 'direction', 'per_page',
        ]));

        return new CustomerCollection($customers);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $customer = $this->customerService->store([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return $this->successResponse(new CustomerResource($customer), 'Customer created successfully', 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $customer->load('creator');

        return $this->successResponse(new CustomerResource($customer), 'Customer retrieved successfully');
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $customer = $this->customerService->update($customer, $request->validated());

        return $this->successResponse(new CustomerResource($customer), 'Customer updated successfully');
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorize('delete', $customer);

        $customer->delete();

        return $this->successResponse(null, 'Customer deleted successfully');
    }

    public function restore(int $id): JsonResponse
    {
        // CRIT-018 fix: Authorize BEFORE restoring
        $customer = Customer::withTrashed()->findOrFail($id);
        $this->authorize('restore', $customer);
        $this->customerService->restore($id);

        return $this->successResponse(new CustomerResource($customer->fresh()->load(['creator'])), 'Customer restored successfully');
    }
}
